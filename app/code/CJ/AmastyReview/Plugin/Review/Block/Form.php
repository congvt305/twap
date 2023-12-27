<?php

namespace CJ\AmastyReview\Plugin\Review\Block;

use Amasty\AdvancedReview\Model\Sources\Recommend;
use Magento\Review\Block\Form as MagentoForm;

class Form
{
    /**
     * @var \Amasty\AdvancedReview\Helper\Config
     */
    private $configHelper;

    /**
     * @var \Amasty\AdvancedReview\Helper\BlockHelper
     */
    private $blockHelper;

    public function __construct(
        \Amasty\AdvancedReview\Helper\Config $configHelper,
        \Amasty\AdvancedReview\Helper\BlockHelper $blockHelper
    ) {
        $this->configHelper = $configHelper;
        $this->blockHelper = $blockHelper;
    }

    /**
     * @param MagentoForm $subject
     * @param $result
     * @return string
     */
    public function afterToHtml(
        MagentoForm $subject,
                    $result
    ) {
        $search = '</fieldset>';
        if (!$this->blockHelper->isAllowGuest() || strpos($result, $search) === false) {
            return $result;
        }

        $searchNickName = '<div class="field review-field-summary required';
        if ($this->configHelper->isEmailFieldEnable()) {
            $replace = $this->getEmailFieldHtml() . $searchNickName;
            $result = substr_replace($result, $replace, strrpos($result, $searchNickName), strlen($searchNickName));
            $result = str_replace('review-field-nickname', 'review-field-nickname -half', $result);
        }

        if ($this->configHelper->isProsConsEnabled()) {
            $replace = $this->getProsConsHtml() . $search;
            $result = substr_replace($result, $replace, strrpos($result, $search), strlen($search));
        }

        $replace = $this->getRecommendFieldHtml();
        $replace .= $this->getGdprFieldHtml();
        if ($replace) {
            $replace .= $search;
            $result = substr_replace($result, $replace, strrpos($result, $search), strlen($search));
        }

        $result = str_replace('block review-add"', 'block review-add amreview-submit-form"', $result);

        return $result;
    }

    /**
     * @return string
     */
    private function getImageUploadHtml()
    {
        $html = '';
        if ($this->blockHelper->isAllowGuest()) {
            $html = sprintf(
                '<div class="field review-field-image %s">
                <label class="label">%s</label><div class="control">
                <label id="am_upload_image_button">%s</label>
                <label id="am_upload_image_label" class="label" style="margin-left: 20px">%s</label>
                <input id="am_upload_image" class="amrev-input" name="review_images[]" accept="image/*" multiple %s type="file" title="%s" hidden="hidden">
                </div></div>',
                $this->configHelper->isImagesRequired() ? 'required' : '',
                __('Add your photo'),
                __("Select upload file"),
                __("No file uploaded"),
                $this->configHelper->isImagesRequired() ? 'data-validate="{required:true}"' : '',
                __('Add your photo')
            );
            $html .= '<script>
                            require(["jquery"], function($){
                                document.getElementById("am_upload_image_button").onclick = function() {showImageUpload()};
                                document.getElementById("am_upload_image").onchange = function() {changeImageText()};
                                function showImageUpload (){
                                            $("#am_upload_image").trigger("click");
                                }
                               function changeImageText(){
                                    var fileName;
                                    if ($("#am_upload_image")[0].files.length > 1){
                                        fileName = $("#am_upload_image")[0].files.length + " files";
                                    } else {
                                              fileName = $("#am_upload_image")[0].files[0].name;
                                              if (fileName.length > 30) {
                                                fileName = fileName.substring(0, 10) + "...";
                                              }
                                            }
                                    $("#am_upload_image_label").text(fileName)
                               }
                            });
            </script>';
        }

        return $html;
    }

    /**
     * @return string
     */
    private function getRecommendFieldHtml()
    {
        if ($this->configHelper->isRecommendFieldEnabled()) {
            $html = sprintf(
                '<div class="field amreview-recommend-wrap">
                <input class="amreview-checkbox"
                    type="checkbox"
                    name="is_recommended"
                    id="is_recommended"
                    value="' . Recommend::RECOMMENDED . '" />
                <label class="amreview-checkboxlabel" for="is_recommended">%s</label>
                ',
                __('I recommend this product')
            );

            $html .= '</div>';
        }

        return $html ?? '';
    }

    /**
     * @return string
     */
    private function getGdprFieldHtml()
    {
        if ($this->configHelper->isEmailFieldEnable() && $this->configHelper->isGDPREnabled()) {
            $html = sprintf(
                '<div class="field required amreview-gdpr-wrap">
                 <input type="checkbox"
                    name="gdpr"
                    class="amreview-checkbox"
                    id="amreview-gdpr-field"
                    title="%s"
                    data-validate="{required:true}"
                    value="1">
                    <label class="label-gdpr amreview-checkboxlabel" for="amreview-gdpr-field">
                        %s<span class="asterix">*</span>
                    </label>
                </div>',
                __('GDPR'),
                $this->configHelper->getGDPRText()
            );
        }

        return $html ?? '';
    }

    /**
     * @return string
     */
    private function getProsConsHtml()
    {
        $html = '';
        if ($this->blockHelper->isAllowGuest()) {
            $html = sprintf(
                '<div class="field amreview-pros-wrap">
                <label for="amreview-pros-field" class="amreview-textfield label">%s</label>
                <textarea id="amreview-pros-field"
                    class="amreview-textfield"
                    name="like_about"
                    rows="3"
                    maxlength="700"
                    data-bind="value: review().like_about"></textarea></div>',
                __('Advantages')
            );

            $html .= sprintf(
                '<div class="field amreview-cons-wrap">
                <label for="amreview-cons-field" class="amreview-textfield label">%s</label>
                <textarea id="amreview-cons-field"
                    class="amreview-textfield"
                    name="not_like_about"
                    rows="3"
                    maxlength="700"
                    data-bind="value: review().not_like_about"></textarea></div>',
                __('Disadvantages')
            );
        }

        return $html;
    }

    /**
     * @return string
     */
    private function getEmailFieldHtml()
    {
        $html = sprintf(
            '<div class="field review-field-email">
                <label for="amreview-email-field" class="amreview-emailfield label">%s</label>
                <input id="amreview-email-field"
                    class="amreview-textfield input-text"
                    type="text"
                    data-validate="{\'validate-email\':true}"
                    name="guest_email"
                    data-bind="value: review().guest_email" /></div>',
            __('Email Address')
        );

        return $html;
    }
}