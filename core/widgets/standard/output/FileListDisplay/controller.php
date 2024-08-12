<?php
namespace RightNow\Widgets;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Utils\Url;

class FileListDisplay extends \RightNow\Libraries\Widget\Output {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        //QA 190626-000022 Thumbnails not appearing after Social -> Community change
        $this->data['displayingSocialAttachments'] = Text::beginsWith($this->data['attrs']['name'], 'Community');
        if (parent::getData() === false && !$this->data['displayingSocialAttachments']) {
            return false;
        }

        if(count($this->data['value'])) {
            $commonAttachments = ($this->data['attrs']['name'] === 'Answer.FileAttachments'
                && ($answerID = Url::getParameter('a_id'))
                && ($answer = $this->CI->model('Answer')->get($answerID)->result))
                    ? (array) $answer->CommonAttachments
                    : array();

            if (!$this->data['attachments'] = $this->getAttachments($this->data['value'], $commonAttachments)) {
                return false;
            }
            $this->data['js']['attachments'] = $this->data['attachments'];
            if ($this->data['attrs']['sort_by_filename']) {
                usort($this->data['attachments'], function($a, $b) {
                    return strcasecmp($a->FileName, $b->FileName);
                });
            }

            // Set up label-value justification
            $this->data['wrapClass'] = ($this->data['attrs']['left_justify']) ? ' rn_LeftJustify' : '';
        }
    }

    /**
     * Returns a list of attachments combined from $input and $commonAttachments.
     * @param mixed $input A Connect File Attachment Array object, if attachments are present.
     * @param array $commonAttachments A Connect File Attachment array, if common attachments are present.
     * @return array The combined list of attachment objects.
     */
    function getAttachments($input, array $commonAttachments) {
        $attachments = array();
        if (!count($input) && !$commonAttachments) {
            return $attachments;
        }

        if (!\RightNow\Utils\Connect::isFileAttachmentType($input)){
            echo $this->reportError(Config::getMessage(FILELISTDISPLAY_DISP_FILE_ATTACH_MSG));
            return $attachments;
        }

        // convert connect object to array and merge in common attachments if present
        $input = (array) $input;
        if ($commonAttachments) {
            $input = array_merge(array_values($input), array_values($commonAttachments));
        }

        $openInNewWindow = trim(Config::getConfig(EU_FA_NEW_WIN_TYPES));
        $baseAttachmentUrl = '/ci/fattach/get/%s/%s' . Url::sessionParameter();
        $attachmentUrl = $baseAttachmentUrl . '/filename/%s';

        if($this->data['displayingSocialAttachments']) {
            $communityAttachmentUrl = Text::beginsWith($this->data['attrs']['name'], 'CommunityQuestion.') ? 'cq' : 'cc';
            $attachmentUrl = $baseAttachmentUrl . "/{$communityAttachmentUrl}/%s/filename/%s?token=%s";
        }

        $showCreatedTime = Text::beginsWith($this->data['attrs']['name'], 'Incident.') || $this->data['displayingSocialAttachments'];
        foreach ($input as $item) {
            if ($item->Private) {
                continue;
            }
            $item->Target = ($openInNewWindow && preg_match("/{$openInNewWindow}/i", $item->ContentType)) ? '_blank' : '_self';
            $item->Icon = \RightNow\Utils\Framework::getIcon($item->FileName);
            $item->ReadableSize = Text::getReadableFileSize($item->Size);
            if($this->data['displayingSocialAttachments']) {
                $attachment = $this->CI->model('FileAttachment')->get($item->ID, $item->CreatedTime)->result;
                $token = \RightNow\Utils\Framework::createCommunityAttachmentToken($item->ID, $item->CreatedTime);
                $item->AttachmentUrl = sprintf($attachmentUrl, $item->ID, $showCreatedTime ? $item->CreatedTime : 0, $attachment->id, urlencode($item->FileName), $token);
            }
            else {
                $item->AttachmentUrl = sprintf($attachmentUrl, $item->ID, $showCreatedTime ? $item->CreatedTime : 0, urlencode($item->FileName));
            }
            $item->ThumbnailUrl = $item->ThumbnailScreenReaderText = null;
            if($this->data['attrs']['display_thumbnail'] && Text::beginsWith($item->ContentType, 'image')) {
                $fileExtension = pathinfo($item->AttachmentUrl, PATHINFO_EXTENSION);
                $fileExtension = Text::getSubstringBefore($fileExtension, '?token', $fileExtension);
                $fileExtension = Text::escapeHtml($fileExtension);
                $item->ThumbnailScreenReaderText = sprintf(Config::getMessage(FILE_TYPE_PCT_S_LBL), $fileExtension);
                // ThumbnailUrl may change in the future, but for now is the same as $item->AttachmentUrl
                $item->ThumbnailUrl = $item->AttachmentUrl;
            }
            $attachments[] = $item;
        }

        return $attachments;
    }
}
