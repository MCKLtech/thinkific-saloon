<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts;

class CreateSiteScript
{
    public function __construct(
        public string  $name,
        public string  $description,
        public array   $page_scopes,
        public ?string $src = null,
        public ?string $content = null,
        public ?string $location = 'footer',
        public ?string $load_method = 'default',
        public ?string $category = 'functional',
    )
    {
    }

}
