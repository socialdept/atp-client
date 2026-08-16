<?php

namespace SocialDept\AtpClient\Data\Responses\Ozone\Server;

use Illuminate\Contracts\Support\Arrayable;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Server\GetConfig\ServiceConfig;
use SocialDept\AtpSchema\Generated\Tools\Ozone\Server\GetConfig\ViewerConfig;

/**
 * @implements Arrayable<string, mixed>
 */
class GetConfigResponse implements Arrayable
{
    public function __construct(
        public readonly ?ServiceConfig $appview = null,
        public readonly ?ServiceConfig $pds = null,
        public readonly ?ServiceConfig $blobDivert = null,
        public readonly ?ServiceConfig $chat = null,
        public readonly ?ViewerConfig $viewer = null,
        public readonly ?string $verifierDid = null,
    ) {
    }

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

    public function toArray(): array
    {
        return [
            'appview' => $this->appview?->toArray(),
            'pds' => $this->pds?->toArray(),
            'blobDivert' => $this->blobDivert?->toArray(),
            'chat' => $this->chat?->toArray(),
            'viewer' => $this->viewer?->toArray(),
            'verifierDid' => $this->verifierDid,
        ];
    }
}
