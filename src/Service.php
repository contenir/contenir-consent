<?php

declare(strict_types=1);

namespace Contenir\Consent;

use Contenir\Consent\Exception\InvalidServiceException;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * Something on the site that needs consent, as the preferences list it under
 * its category: its name, who provides it, the cookies it sets and where its
 * privacy policy is.
 *
 * @api
 */
final readonly class Service
{
    /**
     * @param list<string> $cookies    Cookie names; * matches any run of characters, as in _ga_*
     * @param ?string      $privacyUrl The provider's privacy policy
     *
     * @throws InvalidServiceException When the key, name or privacy link can't be used.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per column the preferences list.
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $provider,
        public Category $category,
        public array $cookies = [],
        public ?string $privacyUrl = null,
    ) {
        if (1 !== preg_match('/^[a-z][a-z0-9-]*$/D', $key)) {
            throw InvalidServiceException::because(sprintf(
                'A service key is lower case letters, digits and dashes, starting with a letter; "%s" is not',
                $key,
            ));
        }

        if ('' === trim($name)) {
            throw InvalidServiceException::because(sprintf('The service "%s" needs a name', $key));
        }

        if (null !== $privacyUrl && ! self::link($privacyUrl)) {
            throw InvalidServiceException::because(sprintf(
                'The privacy link for "%s" must be an http(s) address or a path starting with /',
                $key,
            ));
        }
    }

    /**
     * A service as a site's config declares it:
     *
     *     'youtube' => [
     *         'name'        => 'YouTube',
     *         'provider'    => 'Google',
     *         'category'    => 'preferences',
     *         'cookies'     => ['YSC', 'VISITOR_INFO1_LIVE'],
     *         'privacy_url' => 'https://policies.google.com/privacy',
     *     ],
     *
     * @param array<array-key, mixed> $values
     *
     * @throws InvalidServiceException When a value is missing or can't be used.
     *
     * @mago-expect analysis:mixed-assignment Config is untyped; the cookies are checked here.
     */
    public static function fromArray(string $key, array $values): self
    {
        $category = Category::tryFrom(self::string($values, 'category'));
        if (null === $category) {
            throw InvalidServiceException::because(sprintf(
                'The service "%s" needs a category: necessary, analytics, marketing or preferences',
                $key,
            ));
        }

        $cookies    = $values['cookies'] ?? [];
        $privacyUrl = self::string($values, 'privacy_url');

        return new self(
            $key,
            self::string($values, 'name'),
            self::string($values, 'provider'),
            $category,
            is_array($cookies)
                ? array_values(array_filter(array_map(
                    static fn(mixed $cookie): string => is_string($cookie) ? trim($cookie) : '',
                    $cookies,
                )))
                : [],
            '' === $privacyUrl ? null : $privacyUrl,
        );
    }

    /**
     * Whether $url is safe to link to: an http(s) address or a path on the
     * site.
     */
    public static function link(string $url): bool
    {
        return (
            str_starts_with($url, 'https://')
                || str_starts_with($url, 'http://')
                || str_starts_with($url, '/') && ! str_starts_with($url, '//')
        );
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @mago-expect analysis:mixed-assignment Config is untyped; the value is checked here.
     */
    private static function string(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
