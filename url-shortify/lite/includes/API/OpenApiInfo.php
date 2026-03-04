<?php

namespace KaizenCoders\URL_Shortify\API;

/**
 * OpenAPI global definitions for URL Shortify REST API.
 *
 * This file is NOT loaded by WordPress at runtime — it exists solely so that
 * swagger-php can scan it for @OA\ annotations.
 *
 * @OA\Info(
 *     title="URL Shortify REST API",
 *     version="1.13.1",
 *     description="Manage short links and groups via the URL Shortify WordPress plugin REST API. Tags, Domains, UTM Presets, Tracking Pixels and Auto Link Keywords require URL Shortify PRO.",
 *     @OA\Contact(
 *         name="Kaizen Coders",
 *         email="hello@kaizencoders.com",
 *         url="https://kaizencoders.com"
 *     ),
 *     @OA\License(
 *         name="GPL-2.0+",
 *         url="https://www.gnu.org/licenses/gpl-2.0.html"
 *     )
 * )
 *
 * @OA\Server(
 *     url="{siteUrl}/wp-json/url-shortify/v1",
 *     description="WordPress REST API — set siteUrl to your site's base URL (e.g. https://yourdomain.com).",
 *     @OA\ServerVariable(
 *         serverVariable="siteUrl",
 *         default="https://example.com",
 *         description="Base URL of your WordPress installation (no trailing slash)."
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="basicAuth",
 *     type="http",
 *     scheme="basic",
 *     description="HTTP Basic Auth — use your API Consumer Key as the username and Consumer Secret as the password."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKeyHeader",
 *     type="apiKey",
 *     in="header",
 *     name="X-URL-SHORTIFY-CONSUMER-KEY",
 *     description="Consumer Key header. Must be sent together with X-URL-SHORTIFY-CONSUMER-SECRET."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKeyHeaderSecret",
 *     type="apiKey",
 *     in="header",
 *     name="X-URL-SHORTIFY-CONSUMER-SECRET",
 *     description="Consumer Secret header. Must be sent together with X-URL-SHORTIFY-CONSUMER-KEY."
 * )
 *
 * @OA\Schema(
 *     schema="Link",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="slug", type="string", example="my-link"),
 *     @OA\Property(property="name", type="string", example="My Link"),
 *     @OA\Property(property="url", type="string", format="uri", example="https://example.com/very/long/url"),
 *     @OA\Property(property="short_url", type="string", format="uri", example="https://yourdomain.com/my-link"),
 *     @OA\Property(property="description", type="string", example="A description of the link"),
 *     @OA\Property(property="nofollow", type="integer", enum={0, 1}, example=0),
 *     @OA\Property(property="track_me", type="integer", enum={0, 1}, example=1),
 *     @OA\Property(property="sponsored", type="integer", enum={0, 1}, example=0),
 *     @OA\Property(property="params_forwarding", type="integer", enum={0, 1}, example=0),
 *     @OA\Property(property="params_structure", type="string", example=""),
 *     @OA\Property(property="redirect_type", type="string", enum={"301", "302", "307"}, example="301"),
 *     @OA\Property(property="status", type="integer", enum={0, 1}, example=1),
 *     @OA\Property(property="type", type="string", example="direct"),
 *     @OA\Property(property="type_id", type="integer", example=0),
 *     @OA\Property(property="password", type="string", example=""),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example=null),
 *     @OA\Property(property="cpt_id", type="integer", example=0),
 *     @OA\Property(property="cpt_type", type="string", example=""),
 *     @OA\Property(property="rules", type="string", example=""),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="created_by_id", type="integer", example=1),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_by_id", type="integer", example=1),
 *     @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"), example={1, 2}),
 *     @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"), example={3, 4})
 * )
 *
 * @OA\Schema(
 *     schema="Group",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Marketing"),
 *     @OA\Property(property="description", type="string", example="Marketing campaign links"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="created_by_id", type="integer", example=1),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_by_id", type="integer", example=1),
 *     @OA\Property(property="links_count", type="integer", example=5)
 * )
 *
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="code", type="string", example="rest_bad_request"),
 *     @OA\Property(property="message", type="string", example="Invalid request."),
 *     @OA\Property(property="data", type="object",
 *         @OA\Property(property="status", type="integer", example=400)
 *     )
 * )
 */
class OpenApiInfo {
	// Intentionally empty — this class exists only for swagger-php to scan its docblock.
}
