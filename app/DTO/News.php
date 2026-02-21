<?php

namespace App\DTO;

/**
 * Data Transfer Object representing a single news article.
 */
class News
{
    public function __construct(
        public readonly string $date,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $image = null,
    ) {}

    /**
     * Create a News instance from a raw associative array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'] ?? '',
            title: $data['title'] ?? '',
            url: $data['url'] ?? '',
            image: $data['image'] ?? null,
        );
    }

    /**
     * Convert the DTO to a plain array
     */
    public function toArray(): array
    {
        return [
            'date'  => $this->date,
            'title' => $this->title,
            'url'   => $this->url,
            'image' => $this->image,
        ];
    }
}
