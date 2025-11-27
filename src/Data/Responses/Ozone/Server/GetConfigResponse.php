<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Server;

use SocialDept\AtpSchema\Generated\Tools\Ozone\Server\GetConfig\ServiceConfig;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Server\GetConfig\ViewerConfig;

class GetConfigResponse
{
    public function __construct(
        public readonly ?ServiceConfig $appview = null,
        public readonly ?ServiceConfig $pds = null,
        public readonly ?ServiceConfig $blobDivert = null,
        public readonly ?ServiceConfig $chat = null,
        public readonly ?ViewerConfig $viewer = null,
        public readonly ?string $verifierDid = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            appview: isset($data['appview']) ? ServiceConfig::fromArray($data['appview']) : null,
            pds: isset($data['pds']) ? ServiceConfig::fromArray($data['pds']) : null,
            blobDivert: isset($data['blobDivert']) ? ServiceConfig::fromArray($data['blobDivert']) : null,
            chat: isset($data['chat']) ? ServiceConfig::fromArray($data['chat']) : null,
            viewer: isset($data['viewer']) ? ViewerConfig::fromArray($data['viewer']) : null,
            verifierDid: $data['verifierDid'] ?? null,
        );
    }
}
