<?php

namespace CWP\Core\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

/**
 * Adds capability to augment links with extra attributes and meta information.
 *
 * Usage in the templates:
 *      $Content.RichLinks
 *
 * Note: will only work with content produced by HtmlEditorField.
 */
class RichLinksExtension extends Extension
{

    /**
     * @var array
     */
    private static $casting = [
        'RichLinks' => 'HTMLText'
    ];

    /**
     * @return string
     */
    public function RichLinks()
    {
        // Note:
        // Assume we can use Regexes because the link will always be formatted
        // in the same way coming from the CMS.

        $content = $this->owner->value;

        // Find all file links for processing.
        preg_match_all('/<a.*href="\[file_link,id=([0-9]+)\].*".*>.*<\/a>/U', $content ?? '', $matches);

        // Attach the file type and size to each of the links.
        for ($i = 0; $i < count($matches[0] ?? []); $i++) {
            $file = DataObject::get_by_id(File::class, $matches[1][$i]);
            if ($file) {
                $size = $file->getSize();
                $ext = strtoupper($file->getExtension() ?? '');
                // Replace the closing </a> tag with the size span (and reattach the closing tag).
                $newLink = substr($matches[0][$i] ?? '', 0, strlen($matches[0][$i] ?? '') - 4)
                    . "<span class='fileExt'> [$ext, $size]</span></a>";
                $content = str_replace($matches[0][$i] ?? '', $newLink ?? '', $content ?? '');
            }
        }

        // Inject extra attributes into the external links.
        $pattern = '/(<a.*)(href=\"https?:\/\/[^\"]*\"[^>]*>.*)(<\/a>)/iU';
        $replacement = sprintf(
            '$1class="external" rel="external" $2<span class="nonvisual-indicator">(%s)</span>$3',
            _t(__CLASS__ . '.ExternalLink', 'external link')
        );
        $content = preg_replace($pattern ?? '', $replacement ?? '', $content ?? '', -1);

        return $content;
    }
}
