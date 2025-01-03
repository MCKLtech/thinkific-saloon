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
            slug: $responseData['slug'],
            subtitle: $responseData['subtitle'],
            product_id: $responseData['product_id'],
            description: $responseData['description'],
            course_card_text: $responseData['course_card_text'],
            intro_video_youtube: $responseData['intro_video_youtube'],
            contact_information: $responseData['contact_information'],
            keywords: $responseData['keywords'],
            duration: $responseData['duration'],
            banner_image_url: $responseData['banner_image_url'],
            course_card_image_url: $responseData['course_card_image_url'],
            intro_video_wistia_identifier: $responseData['intro_video_wistia_identifier'],
            administrator_user_ids: $responseData['administrator_user_ids'],
            chapter_ids: $responseData['chapter_ids'],
            reviews_enabled: $responseData['reviews_enabled'],
            user_id: $responseData['user_id'],
            instructor_id: $responseData['instructor_id']
        );
    }
}