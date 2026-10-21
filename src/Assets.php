<?php

declare(strict_types=1);

namespace Contenir\Consent;

use function dirname;
use function file_get_contents;
use function hash;
use function sprintf;
use function substr;

/**
 * The banner's browser files, for a site adapter to serve: vanilla-
 * cookieconsent 3.1.0 (MIT, Orest Bida) with Consent's own script and
 * stylesheet after it, each as one file. Their names carry a hash of their
 * contents, so a site can cache them for a year.
 *
 * @api
 */
final class Assets
{
    /**
     * The vanilla-cookieconsent release bundled in assets/.
     */
    public const string COOKIECONSENT = '3.1.0';

    /**
     * The first twelve characters of the SHA-256 of the script and the
     * stylesheet together. The package's tests fail when a file changes and
     * this doesn't.
     */
    public const string HASH = '9387849d60c0';

    private const array SCRIPTS = ['cookieconsent.umd.js', 'contenir-consent.js'];

    private const array STYLESHEETS = ['cookieconsent.css', 'contenir-consent.css'];

    /**
     * The hash of the files as they are now, which HASH should match.
     */
    public static function hash(): string
    {
        return substr(hash('sha256', self::script() . self::stylesheet()), offset: 0, length: 12);
    }

    /**
     * The script: cookieconsent, then window.ContenirConsent.
     */
    public static function script(): string
    {
        return self::join(self::SCRIPTS);
    }

    /**
     * The file name to serve the script under: consent-<hash>.js.
     */
    public static function scriptName(): string
    {
        return 'consent-' . self::HASH . '.js';
    }

    /**
     * The stylesheet: cookieconsent's, then Consent's changes to it.
     */
    public static function stylesheet(): string
    {
        return self::join(self::STYLESHEETS);
    }

    /**
     * The file name to serve the stylesheet under: consent-<hash>.css.
     */
    public static function stylesheetName(): string
    {
        return 'consent-' . self::HASH . '.css';
    }

    /**
     * @param list<string> $files
     */
    private static function join(array $files): string
    {
        $contents = '';
        foreach ($files as $file) {
            $contents .= sprintf("%s\n", file_get_contents(dirname(__DIR__) . '/assets/' . $file));
        }

        return $contents;
    }
}
