<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 12:16
 */

namespace Imper86\OauthClient\Constants;
/**
 * Class GrantType
 * @package Imper86\OauthClient\Constants
 */
final class GrantType
{
    const AUTHORIZATION_CODE = 'authorization_code';

    const REFRESH_TOKEN = 'refresh_token';

    const CLIENT_CREDENTIALS = 'client_credentials';
}
