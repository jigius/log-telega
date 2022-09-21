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

/**
 * Interface TelegramLogInterface
 */
interface TelegramLogInterface extends Log\LogInterface
{
    /**
     * Defines a chat id
     *
     * @param int $id
     * @return TelegramLog
     */
    public function withChatId(int $id): TelegramLog;

    /**
     * Defines a request uri
     *
     * @param string $uri
     * @return TelegramLog
     */
    public function withRequestUri(string $uri): TelegramLog;
}
