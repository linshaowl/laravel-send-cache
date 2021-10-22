<?php

/**
 * (c) linshaowl <linshaowl@163.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lswl\SendCaches\Exceptions;

interface ErrorCodeInterface
{
    public const PARAMETER_TO_MUST = 401;
    public const PARAMETER_CODE_MUST = 402;
    public const WAIT_INTERVAL = 403;
    public const SEND_FREQUENTLY = 404;
    public const SEND_FAILURE = 405;
    public const INVALID_CODE = 410;
    public const CODE_NOT_CORRECT = 411;
}
