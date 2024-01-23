# wooninja-thinkific-saloon

A PHP SDK for the Thinkific API implemented using Saloon

## Why Saloon?

I wrote my original PHP library for Thinkific in ~2019 and since then there have been some major improvements in both
PHP and libraries such as Saloon. With that in mind, I wanted to make the library more robust, strongly typed and easier
to maintain. In addition, Saloon has inbuilt support for pagination and rate limiting, lowering technical overhead when
dealing with busy integration scenarios.

## REST vs GraphQL

At time of writing (Jan 2024), Thinkific is slowly introducing a GraphQL API. This GraphQL API does not currently
replicate the complete functionality of the REST API. The intention of this library is to abstract the underlying API
call, meaning as and when GraphQL matures, end points will be updated to make use them where applicable.

## Installation

This library requires PHP 8.0 and later

The recommended way to install is using Composer

NOte: This library is intended to speed up development time but is not a shortcut to reading the Thinkific
documentation. Many endpoints require specific and required fields for successful operation. Always read the
documentation before using an endpoint.

```sh
composer require mckltech/wooninja-thinkific-saloon
```

## Data Transfer Objects (DTOs)

The library makes extensive use of DTOs for entities such a Users, Courses, Products and Enrollments. Most endpoints
return a DTO or a collection of DTOs. The DTOs are strongly typed and will throw exceptions if required fields are not
present, in addition to the request failing if no DTO can be created.

When creating an entity, such as a User, or updating one, for example updating an enrollment, you will be required to pass a DTO. Again, these are strongly typed and will throw exceptions if required fields are not present.

## Client - API Key

Initialize your client using your access token:

```php
$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "XXXX428d55aabXXXXX68c0fXXXX",
  "example-school-123"
);
```

> - You can find your API Key by following the Thinkific API
    documentation: https://developers.thinkific.com/api/api-key-auth
>
> - For your subdomain, do not include .thinkific.com. For example, if your subdomain is example.thinkific.com, then you
    would use 'example' in your ThinkificClient set up. If you are using a custom domain, you should retrieve your
    Thinkific sub-domain from your Thinkific dashboard.

## Client - OAuth

The library permits the use of OAuth Access Tokens for API access, in addition to containing a helper method for
refreshing. Note, the library does not implement the OAuth flow itself. I recommend using a standalone library for this
e.g. Laravel Socialite

```php
$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "XXXX428d55aabXXXXX68c0fXXXX",
  "example-school-123",
  true
);
```

## Support, Issues & Bugs

This library is unofficial and is not endorsed or supported by Thinkific.

For bugs and issues, open an issue in this repo and feel free to submit a PR. Any issues that do not contain full logs
or explanations will be closed. We need you to help us help you!

## Example Operations

```php
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;

$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "XXXX",
  "subdomain"
);

/* Fetch all Users */
$users = $client->users->users();

/* Paginate through all users */
foreach ($users->items() as $user) {
  /* Fetch a single user */ 
  $fetchedUser = $client->users->get($user->id);

}

/* Create a new User */
$newUser = new CreateUser(
  first_name: "John",
  last_name: "Doe",
  email: "john.doe@example.com",
  password: null,
  skip_custom_fields_validation: true,
  send_welcome_email: true,
  custom_profile_fields: null,
  roles: null,
  bio: "An example bio for the new student",
  company: "WooNinja Software",
  headline: null,
  affiliate_code: null,
  affiliate_commission: null,
  affiliate_commission_type: null,
  affiliate_payout_email: null,
  external_id: null,
  provider: null
);

$newUser = $client->users->create($newUser);

```

## Supported Endpoints

All endpoints follow a similar mechanism to the examples show above. Again, please ensure you read the Thinkific API
documentation prior to use as there are numerous required fields for most POST/PUT operations.

- Bundles
- Chapters
- Contents
- Coupons
- Courses
- Course Reviews
- Custom Profile Field Definitions
- Enrollments
- Groups
- Instructors
- Products
- Promotions
- Users
- Webhooks
- OAuth Helper (Refresh Token Only)

## Exceptions

Exceptions are handled by Saloon. Most end points for retrieving data will either return a DTO (or collection of DTOs)
or fail. Further docs here: https://docs.saloon.dev/the-basics/handling-failures

## Credit

The layout and methodology used in this library was inspired by Ash Allen from https://battle-ready-laravel.com/


