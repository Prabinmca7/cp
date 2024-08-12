<?

namespace RightNow\Decorators;

/**
 * Decorator to help with the presentation of files
 */
class FilePresenter extends Base {
    private $attachmentUrl;
    protected $connectTypes = array(
        'FileAttachmentAnswer',
        'FileAttachmentIncident',
        'FileAttachmentCommunity',
    );

    /**
     * Sets the file's attachment URL.
     */
    protected function decoratorAdded () {
        $this->attachmentUrl = "/ci/fattach/get/%s/%s" . \RightNow\Utils\Url::sessionParameter() . "/filename/%s";
    }

    /**
     * The URL target to use.
     * @return string The target to use
     */
    function target () {
        static $openFileTypesInNewWindow;
        if (is_null($openFileTypesInNewWindow)) {
            $openFileTypesInNewWindow = trim(\RightNow\Utils\Config::getConfig(EU_FA_NEW_WIN_TYPES));
        }

        return ($openFileTypesInNewWindow && preg_match("/{$openFileTypesInNewWindow}/i", $this->connectObj->ContentType))
            ? '_blank'
            : '_self';
    }

    /**
     * The filename.
     * @return string The filename
     */
    function name () {
        return $this->connectObj->Name ?: $this->connectObj->FileName;
    }

    /**
     * Icon representation of the file.
     * @return string HTML for the icon to represent the file
     */
    function icon () {
        return \RightNow\Utils\Framework::getIcon($this->connectObj->FileName);
    }

    /**
     * The file size.
     * @return int The file's size
     */
    function fileSize () {
        return \RightNow\Utils\Text::getReadableFileSize($this->connectObj->Size);
    }

    /**
     * The file's URL.
     * @param Boolean $useCreationTime Whether or not to include the created time of the file in the URL
     * @return string The URL to access the file
     */
    function url ($useCreationTime = false) {
        $file = $this->connectObj;
        $fileName = urlencode($file->FileName);

        return sprintf($this->attachmentUrl, $file->ID, $useCreationTime ? $file->CreatedTime : 0, $fileName);
    }

    /**
     * The file's extension.
     * @return string The file extension
     */
    function fileExtension () {
        return pathinfo($this->connectObj->FileName, PATHINFO_EXTENSION);
    }

    /**
     * The file's thumbnail URL. ThumbnailUrl may change in the future, but for now is the same as $item->AttachmentUrl.
     * @param Boolean $useCreationTime Whether or not to include the created time of the file in the URL
     * @return string The thumbnail URL
     */
    function thumbnailUrl ($useCreationTime = false) {
        return $this->url($useCreationTime);
    }

    /**
     * Whether a thumbnail could be displayed for this file.
     * @return Boolean True if the file's content type starts with 'image'
     */
    function ableToDisplayThumbnail () {
        return \RightNow\Utils\Text::beginsWith($this->connectObj->ContentType, 'image');
    }

    /**
     * Whether the file is private.
     * @return Boolean True is the file is private
     */
    function isPrivate () {
        return $this->connectObj->Private;
    }
}
