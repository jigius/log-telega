<?php
/**
 * This file is part of the j6s-acc/log-telegram library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) 2021 Jigius <jigius@gmail.com>
 */

namespace Acc\Core\Log\Telega;

use Acc\Core\Log;
use Acc\Core\SerializableInterface;
use RuntimeException;
use LogicException;

/**
 * Class TelegramLog
 *
 * Does the passing of log entries into a specified Telegram's group
 */
final class TelegramLog implements TelegramLogInterface, SerializableInterface, Log\LogEmbeddableInterface
{
    /**
     * @var array
     */
    private array $i;
    /**
     * @var Log\LogLevelInterface
     */
    private Log\LogLevelInterface $minLevel;
    /**
     * @var Log\LogInterface
     */
    private Log\LogInterface $original;
    /**
     * @var Log\ProcessableEntryInterface
     */
    private $p;

    /**
     * TelegramLog constructor.
     *
     * @param Log\LogInterface $log
     * @param Log\ProcessableEntryInterface|null $p
     */
    public function __construct(Log\LogInterface $log, ?Log\ProcessableEntryInterface $p = null)
    {
        $this->i = [];
        $this->original = $log;
        $this->p = $p ?? new VanillaProcessedEntry();
        $this->minLevel = new Log\LogLevel(Log\LogLevelInterface::INFO);
    }

    /**
     * @inheritdoc
     */
    public function withChatId(int $id): TelegramLog
    {
        $obj = $this->blueprinted();
        $obj->i['chatId'] = $id;
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function withRequestUri(string $uri): TelegramLog
    {
        $obj = $this->blueprinted();
        $obj->i['requestUri'] = $uri;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withEntry(Log\LogEntryInterface $entity): self
    {
        $obj = $this->blueprinted();
        if ($entity->level()->lt($this->minLevel)) {
            $obj->original = $this->original->withEntry($entity);
            return $obj;
        }
        $obj->original = $this->original->withEntry($entity);
        if (!isset($this->i['chatId']) || !isset($this->i['requestUri'])) {
            throw new LogicException("Not initialized in a proper way");
        }
        if (($ch = curl_init($this->i['requestUri'])) === false) {
            throw new RuntimeException("Couldn't initialize a connect to a CURL-handler");
        }
        $opts = [
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $this->i['chatId'],
                'text' => $this->p->entry($entity)
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ];
        foreach ($opts as $p => $v) {
            if (curl_setopt($ch, $p, $v) === false) {
                throw
                    new RuntimeException(
                        "Couldn't set an option for CURL-handler",
                        0,
                        new RuntimeException(curl_error($ch), curl_errno($ch))
                    );
            }
        }
        if (($result = curl_exec($ch)) === false) {
            throw
                new RuntimeException(
                    "Couldn't do an exec-request on CURL-handler",
                    0,
                    new RuntimeException(curl_error($ch), curl_errno($ch))
                );
        }
        curl_close($ch);
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function serialized(): array
    {
        return [
            'i' => $this->i,
            'minLevel' => $this->minLevel->toInt(),
            'original' => [
                'classname' => get_class($this->original),
                'state' => $this->original->serialized()
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function unserialized(iterable $data): self
    {
        if (
            !isset($data['minLevel']) || !is_int($data['minLevel']) ||
            !isset($data['i']) || !is_array($data['i']) ||
            !isset($data['original']['classname']) || !is_string($data['original']['classname']) ||
            !class_exists($data['original']['classname']) ||
            !isset($data['original']['state']) || !is_array($data['original']['state'])
        ) {
            throw new LogicException("type invalid");
        }
        $log = new $data['original']['classname']();
        if (!($log instanceof Log\LogInterface)) {
            throw new LogicException("type invalid");
        }
        $obj = $this->blueprinted();
        $obj->original = $log->unserialized($data['original']['state']);
        $obj->minLevel = new Log\LogLevel($data['minLevel']);
        $obj->i = $data['i'];
        return $obj;
    }

    /**
     * Clones the instance
     * @return $this
     */
    private function blueprinted(): self
    {
        $obj = $this->created();
        $obj->i = $this->i;
        $obj->minLevel = $this->minLevel;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withMinLevel(Log\LogLevelInterface $level): self
    {
        $obj = $this->blueprinted();
        $obj->minLevel = $level;
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function withEmbedded(Log\LogInterface $log): self
    {
        $obj = $this->blueprinted();
        if ($this->original instanceof Log\LogEmbeddableInterface) {
            $obj->original = $this->original->withEmbedded($log);
        } else {
            $obj->original = $log;
        }
        return $obj;
    }

    /**
     * @inheritdoc
     */
    public function created(): self
    {
        return new self($this->original, $this->p);
    }
}
