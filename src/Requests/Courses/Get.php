<?php

namespace WooNinja\ThinkificSaloon\Requests\Courses;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;

final class Get extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $course_id,
    )
    {
    }

    public function resolveEndpoint(): string
    {
        return "courses/{$this->course_id}";
    }

    public function createDtoFromResponse(Response $response): Course
    {
        $responseData = $response->json();

        return new Course(
            id: $responseData['id'],
            name: $responseData['name'],
            slug: $responseData['slug'] ?? '',
            subtitle: $responseData['subtitle'] ?? null,
            product_id: $responseData['product_id'],
            description: $responseData['description'] ?? null,
            course_card_text: $responseData['course_card_text'] ?? null,
            intro_video_youtube: $responseData['intro_video_youtube'] ?? null,
            contact_information: $responseData['contact_information'] ?? null,
            keywords: $responseData['keywords'] ?? null,
            duration: $responseData['duration'] ?? null,
            banner_image_url: $responseData['banner_image_url'] ?? '',
            course_card_image_url: $responseData['course_card_image_url'] ?? '',
            intro_video_wistia_identifier: $responseData['intro_video_wistia_identifier'] ?? null,
            administrator_user_ids: $responseData['administrator_user_ids'] ?? [],
            chapter_ids: $responseData['chapter_ids'] ?? [],
            reviews_enabled: $responseData['reviews_enabled'],
            user_id: $responseData['user_id'] ?? null,
            instructor_id: $responseData['instructor_id'] ?? null
        );
    }
}