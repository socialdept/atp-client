<?php

namespace SocialDept\AtpClient\Crypto;

class P256 extends AbstractKeypair
{
    const CURVE = 'secp256r1';

    const ALG = 'ES256';

    const MULTIBASE_PREFIX = "\x80\x24";
}
