<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 3.0).
 * This license is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/gpl-3.0.en.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category    Maxserv: MaxServ_YoastSeo
 * @package     Maxserv: MaxServ_YoastSeo
 * @author      Vincent Hornikx <vincent.hornikx@maxserv.com>
 * @copyright   Copyright (c) 2017 MaxServ (http://www.maxserv.com)
 * @license     http://opensource.org/licenses/gpl-3.0.en.php General Public License (GPL 3.0)
 *
 */

namespace MaxServ\YoastSeo\Ui\Catalog\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\LayoutInterface;
use Magento\Ui\Component\Container;
use MaxServ\YoastSeo\Helper\ImageHelper;

class YoastSeo extends AbstractModifier
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var bool
     */
    protected $moduleIsDisabled = false;

    /**
     * @var string
     */
    protected $seoAttributeGroupCode;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ArrayManager $arrayManager
     * @param ImageHelper $imageHelper
     * @param LayoutInterface $layout
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ArrayManager $arrayManager,
        ImageHelper $imageHelper,
        LayoutInterface $layout
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->arrayManager = $arrayManager;
        $this->imageHelper = $imageHelper;
        $this->layout = $layout;

        $this->moduleIsDisabled = (bool)$this->scopeConfig->getValue('advanced/modules_disable_output/MaxServ_YoastSeo');
        $this->seoAttributeGroupCode = (string)$this->scopeConfig->getValue('yoastseo/advanced/product_seo_attribute_group_code');
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        if ($this->moduleIsDisabled) {
            return $data;
        }

        foreach ($data as &$item) {
            $this->updateImageData($item['product'], 'facebook');
            $this->updateImageData($item['product'], 'twitter');
        }

        return $data;
    }

    /**
     * @param array $item
     * @param string $type
     */
    protected function updateImageData(&$item, $type)
    {
        $field = "yoast_{$type}_image";
        $image = [];
        if (isset($item[$field]) && $item[$field]) {
            $img = $item[$field];
            $image[] = [
                'name' => $img,
                'url' => $this->imageHelper->getYoastImage($img)
            ];
        }
        if ($image) {
            $item[$field] = $image;
        } else {
            unset($item[$field]);
        }
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if ($this->moduleIsDisabled) {
            return $meta;
        }

        $this->setYoastFieldClasses($meta);

        return $meta;
    }

    /**
     * @param array $meta
     */
    protected function setYoastFieldClasses(&$meta)
    {
        foreach ($meta as $groupKey => $group) {
            if (!isset($group['children'])) {
                continue;
            }
            foreach ($group['children'] as $containerKey => $container) {
                if (!isset($container['children'])) {
                    continue;
                }
                foreach ($container['children'] as $fieldKey => $field) {
                    switch ($fieldKey) {
                        case 'name':
                            $meta[$groupKey]['children'][$containerKey]['children'][$fieldKey]['arguments']['data']['config']['additionalClasses'] = 'yoastBox-title';
                            break;
                        case 'description':
                            $meta[$groupKey]['children'][$containerKey]['children'][$fieldKey]['arguments']['data']['config']['additionalClasses'] = 'yoastBox-content';
                    }
                }
            }
        }

        $groupCode = $this->seoAttributeGroupCode;

        $meta[$groupCode]['arguments']['data']['config']['label'] = __('Yoast SEO');
        $meta[$groupCode]['children']['container_url_key']['children']['url_key']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-urlKey';
        $meta[$groupCode]['children']['container_meta_title']['children']['meta_title']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-metaTitle hidden';
        $meta[$groupCode]['children']['container_meta_description']['children']['meta_description']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-metaDescription hidden';
        $meta[$groupCode]['children']['container_meta_keyword']['children']['meta_keyword']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-metaKeywords';
        $meta[$groupCode]['children']['container_focus_keyword']['children']['focus_keyword']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-focusKeyword';
        $meta[$groupCode]['children']['container_yoast_robots_instructions']['children']['yoast_robots_instructions']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-robotsInstructions';

        $meta[$groupCode]['children']['container_yoastBox_block'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Container::NAME,
                        'componentType' => Container::NAME,
                        'breakLine' => false,
                        'label' => 'Yoast',
                        'required' => true,
                        'sortOrder' => 100
                    ]
                ]
            ],
            'children' => [
                'yoastBox_block' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'formElement' => Container::NAME,
                                'componentType' => Container::NAME,
                                'template' => 'ui/form/components/complex',
                                'content' => $this->layout->createBlock('MaxServ\YoastSeo\Block\Adminhtml\YoastBox')->toHtml(),
                                'sortOrder' => 10
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // facebook fieldset
        $meta['yoast-facebook']['arguments']['data']['config']['label'] = '';
        $meta['yoast-facebook']['arguments']['data']['config']['collapsible'] = false;
        $meta['yoast-facebook']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-facebook-fieldset';
        $this->updateImageConfiguration($meta['yoast-facebook']['children']['container_yoast_facebook_image']['children']['yoast_facebook_image']['arguments']['data']['config'], 'facebook');

        // twitter fieldset
        $meta['yoast-twitter']['arguments']['data']['config']['label'] = '';
        $meta['yoast-twitter']['arguments']['data']['config']['collapsible'] = false;
        $meta['yoast-twitter']['arguments']['data']['config']['additionalClasses'] = 'yoastBox-twitter-fieldset';
        $this->updateImageConfiguration($meta['yoast-twitter']['children']['container_yoast_twitter_image']['children']['yoast_twitter_image']['arguments']['data']['config'], 'twitter');
    }

    /**
     * @param array $image
     * @param string $type
     */
    protected function updateImageConfiguration(&$image, $type)
    {
        $image = array_merge($image, [
            'additionalClasses' => 'yoast_' . $type . '_image',
            'dataType' => 'string',
            'formElement' => 'fileUploader',
            'elementTmpl' => 'ui/form/element/uploader/uploader',
            'previewTmpl' => 'Magento_Catalog/image-preview',
            'uploaderConfig' => [
                'url' => 'yoast/image/upload',
                'formData' => [
                    'yoast_image_key' => 'product[yoast_' . $type . '_image]'
                ]
            ]
        ]);
    }
}
