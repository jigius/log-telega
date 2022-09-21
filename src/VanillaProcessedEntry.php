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
use Acc\Core\Log\LogEntryInterface;
use LogicException;

/**
 * Class VanillaProcessedEntry
 *
 * Realizes a base way for the log entries formatting
 */
final class VanillaProcessedEntry implements Log\ProcessableEntryInterface
{
    /**
     * VanillaProcessedEntry constructor.
     */
    public function __construct()
    {
    }

    /**
     * @inheritdoc
     * @throws LogicException
     */
    public function entry(LogEntryInterface $entry): string
    {
        $i = $entry->serialized();
        if (!isset($i['dt']) || !isset($i['level']) || !isset($i['text'])) {
            throw new LogicException("invalid type");
        }
        return
            sprintf(
                "%s\n%s\n===\n%s\n",
                $i['dt'],
                $entry->level()->toString(),
                $i['text']
            );
    }
}
