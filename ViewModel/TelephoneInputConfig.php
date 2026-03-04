<?php

namespace Dotdigitalgroup\Sms\ViewModel;

use Dotdigitalgroup\Sms\Model\Config\Configuration;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class TelephoneInputConfig implements ArgumentInterface
{
    /**
     * @var Configuration
     */
    private $moduleConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * TelephoneInputConfig constructor.
     * @param Configuration $moduleConfig
     * @param RequestInterface $request
     * @param Escaper $escaper
     * @param SerializerInterface $serializer
     * @param AssetRepository $assetRepository
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     */
    public function __construct(
        Configuration $moduleConfig,
        RequestInterface $request,
        Escaper $escaper,
        SerializerInterface $serializer,
        AssetRepository $assetRepository,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->request = $request;
        $this->escaper = $escaper;
        $this->serializer = $serializer;
        $this->assetRepository = $assetRepository;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Get config block for frontend.
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        $config  = [
            "nationalMode" => false,
            "strictMode" => true,
            "countryOrder" => [$this->moduleConfig->getPreferredCountry($websiteId)],
            "initialCountry" => $this->moduleConfig->getPreferredCountry($websiteId)
        ];
        $supportedCountryCodes = $this->getIntlSupportedCountryCodes();
        if ($this->moduleConfig->getAllowedCountries($websiteId)) {
            $config["onlyCountries"] = array_map(function ($countryCode) use ($supportedCountryCodes) {
                if (!in_array($countryCode, $supportedCountryCodes)) {
                    return null;
                }
                return $this->escaper->escapeJs(strtolower($countryCode));
            }, explode(",", $this->moduleConfig->getAllowedCountries($websiteId)));
            $config["onlyCountries"] = array_values(array_filter($config["onlyCountries"]));
        }

        return $this->serializer->serialize($config);
    }

    /**
     * Returns a list of supported country codes from intl-tel-input.
     *
     * @return array
     */
    private function getIntlSupportedCountryCodes(): array
    {
        $countries = [
            ['name' => 'United States', 'iso2' => 'us'],
            ['name' => 'Afghanistan', 'iso2' => 'af'],
            ['name' => 'Åland Islands', 'iso2' => 'ax'],
            ['name' => 'Albania', 'iso2' => 'al'],
            ['name' => 'Algeria', 'iso2' => 'dz'],
            ['name' => 'American Samoa', 'iso2' => 'as'],
            ['name' => 'Andorra', 'iso2' => 'ad'],
            ['name' => 'Angola', 'iso2' => 'ao'],
            ['name' => 'Anguilla', 'iso2' => 'ai'],
            ['name' => 'Antigua & Barbuda', 'iso2' => 'ag'],
            ['name' => 'Argentina', 'iso2' => 'ar'],
            ['name' => 'Armenia', 'iso2' => 'am'],
            ['name' => 'Aruba', 'iso2' => 'aw'],
            ['name' => 'Ascension Island', 'iso2' => 'ac'],
            ['name' => 'Australia', 'iso2' => 'au'],
            ['name' => 'Austria', 'iso2' => 'at'],
            ['name' => 'Azerbaijan', 'iso2' => 'az'],
            ['name' => 'Bahamas', 'iso2' => 'bs'],
            ['name' => 'Bahrain', 'iso2' => 'bh'],
            ['name' => 'Bangladesh', 'iso2' => 'bd'],
            ['name' => 'Barbados', 'iso2' => 'bb'],
            ['name' => 'Belarus', 'iso2' => 'by'],
            ['name' => 'Belgium', 'iso2' => 'be'],
            ['name' => 'Belize', 'iso2' => 'bz'],
            ['name' => 'Benin', 'iso2' => 'bj'],
            ['name' => 'Bermuda', 'iso2' => 'bm'],
            ['name' => 'Bhutan', 'iso2' => 'bt'],
            ['name' => 'Bolivia', 'iso2' => 'bo'],
            ['name' => 'Bosnia & Herzegovina', 'iso2' => 'ba'],
            ['name' => 'Botswana', 'iso2' => 'bw'],
            ['name' => 'Brazil', 'iso2' => 'br'],
            ['name' => 'British Indian Ocean Territory', 'iso2' => 'io'],
            ['name' => 'British Virgin Islands', 'iso2' => 'vg'],
            ['name' => 'Brunei', 'iso2' => 'bn'],
            ['name' => 'Bulgaria', 'iso2' => 'bg'],
            ['name' => 'Burkina Faso', 'iso2' => 'bf'],
            ['name' => 'Burundi', 'iso2' => 'bi'],
            ['name' => 'Cambodia', 'iso2' => 'kh'],
            ['name' => 'Cameroon', 'iso2' => 'cm'],
            ['name' => 'Canada', 'iso2' => 'ca'],
            ['name' => 'Cape Verde', 'iso2' => 'cv'],
            ['name' => 'Caribbean Netherlands', 'iso2' => 'bq'],
            ['name' => 'Cayman Islands', 'iso2' => 'ky'],
            ['name' => 'Central African Republic', 'iso2' => 'cf'],
            ['name' => 'Chad', 'iso2' => 'td'],
            ['name' => 'Chile', 'iso2' => 'cl'],
            ['name' => 'China', 'iso2' => 'cn'],
            ['name' => 'Christmas Island', 'iso2' => 'cx'],
            ['name' => 'Cocos (Keeling) Islands', 'iso2' => 'cc'],
            ['name' => 'Colombia', 'iso2' => 'co'],
            ['name' => 'Comoros', 'iso2' => 'km'],
            ['name' => 'Congo - Brazzaville', 'iso2' => 'cg'],
            ['name' => 'Congo - Kinshasa', 'iso2' => 'cd'],
            ['name' => 'Cook Islands', 'iso2' => 'ck'],
            ['name' => 'Costa Rica', 'iso2' => 'cr'],
            ['name' => 'Côte d\'Ivoire', 'iso2' => 'ci'],
            ['name' => 'Croatia', 'iso2' => 'hr'],
            ['name' => 'Cuba', 'iso2' => 'cu'],
            ['name' => 'Curaçao', 'iso2' => 'cw'],
            ['name' => 'Cyprus', 'iso2' => 'cy'],
            ['name' => 'Czechia', 'iso2' => 'cz'],
            ['name' => 'Denmark', 'iso2' => 'dk'],
            ['name' => 'Djibouti', 'iso2' => 'dj'],
            ['name' => 'Dominica', 'iso2' => 'dm'],
            ['name' => 'Dominican Republic', 'iso2' => 'do'],
            ['name' => 'Ecuador', 'iso2' => 'ec'],
            ['name' => 'Egypt', 'iso2' => 'eg'],
            ['name' => 'El Salvador', 'iso2' => 'sv'],
            ['name' => 'Equatorial Guinea', 'iso2' => 'gq'],
            ['name' => 'Eritrea', 'iso2' => 'er'],
            ['name' => 'Estonia', 'iso2' => 'ee'],
            ['name' => 'Eswatini', 'iso2' => 'sz'],
            ['name' => 'Ethiopia', 'iso2' => 'et'],
            ['name' => 'Falkland Islands (Islas Malvinas)', 'iso2' => 'fk'],
            ['name' => 'Faroe Islands', 'iso2' => 'fo'],
            ['name' => 'Fiji', 'iso2' => 'fj'],
            ['name' => 'Finland', 'iso2' => 'fi'],
            ['name' => 'France', 'iso2' => 'fr'],
            ['name' => 'French Guiana', 'iso2' => 'gf'],
            ['name' => 'French Polynesia', 'iso2' => 'pf'],
            ['name' => 'Gabon', 'iso2' => 'ga'],
            ['name' => 'Gambia', 'iso2' => 'gm'],
            ['name' => 'Georgia', 'iso2' => 'ge'],
            ['name' => 'Germany', 'iso2' => 'de'],
            ['name' => 'Ghana', 'iso2' => 'gh'],
            ['name' => 'Gibraltar', 'iso2' => 'gi'],
            ['name' => 'Greece', 'iso2' => 'gr'],
            ['name' => 'Greenland', 'iso2' => 'gl'],
            ['name' => 'Grenada', 'iso2' => 'gd'],
            ['name' => 'Guadeloupe', 'iso2' => 'gp'],
            ['name' => 'Guam', 'iso2' => 'gu'],
            ['name' => 'Guatemala', 'iso2' => 'gt'],
            ['name' => 'Guernsey', 'iso2' => 'gg'],
            ['name' => 'Guinea', 'iso2' => 'gn'],
            ['name' => 'Guinea-Bissau', 'iso2' => 'gw'],
            ['name' => 'Guyana', 'iso2' => 'gy'],
            ['name' => 'Haiti', 'iso2' => 'ht'],
            ['name' => 'Honduras', 'iso2' => 'hn'],
            ['name' => 'Hong Kong', 'iso2' => 'hk'],
            ['name' => 'Hungary', 'iso2' => 'hu'],
            ['name' => 'Iceland', 'iso2' => 'is'],
            ['name' => 'India', 'iso2' => 'in'],
            ['name' => 'Indonesia', 'iso2' => 'id'],
            ['name' => 'Iran', 'iso2' => 'ir'],
            ['name' => 'Iraq', 'iso2' => 'iq'],
            ['name' => 'Ireland', 'iso2' => 'ie'],
            ['name' => 'Isle of Man', 'iso2' => 'im'],
            ['name' => 'Israel', 'iso2' => 'il'],
            ['name' => 'Italy', 'iso2' => 'it'],
            ['name' => 'Jamaica', 'iso2' => 'jm'],
            ['name' => 'Japan', 'iso2' => 'jp'],
            ['name' => 'Jersey', 'iso2' => 'je'],
            ['name' => 'Jordan', 'iso2' => 'jo'],
            ['name' => 'Kazakhstan', 'iso2' => 'kz'],
            ['name' => 'Kenya', 'iso2' => 'ke'],
            ['name' => 'Kiribati', 'iso2' => 'ki'],
            ['name' => 'Kosovo', 'iso2' => 'xk'],
            ['name' => 'Kuwait', 'iso2' => 'kw'],
            ['name' => 'Kyrgyzstan', 'iso2' => 'kg'],
            ['name' => 'Laos', 'iso2' => 'la'],
            ['name' => 'Latvia', 'iso2' => 'lv'],
            ['name' => 'Lebanon', 'iso2' => 'lb'],
            ['name' => 'Lesotho', 'iso2' => 'ls'],
            ['name' => 'Liberia', 'iso2' => 'lr'],
            ['name' => 'Libya', 'iso2' => 'ly'],
            ['name' => 'Liechtenstein', 'iso2' => 'li'],
            ['name' => 'Lithuania', 'iso2' => 'lt'],
            ['name' => 'Luxembourg', 'iso2' => 'lu'],
            ['name' => 'Macao', 'iso2' => 'mo'],
            ['name' => 'Madagascar', 'iso2' => 'mg'],
            ['name' => 'Malawi', 'iso2' => 'mw'],
            ['name' => 'Malaysia', 'iso2' => 'my'],
            ['name' => 'Maldives', 'iso2' => 'mv'],
            ['name' => 'Mali', 'iso2' => 'ml'],
            ['name' => 'Malta', 'iso2' => 'mt'],
            ['name' => 'Marshall Islands', 'iso2' => 'mh'],
            ['name' => 'Martinique', 'iso2' => 'mq'],
            ['name' => 'Mauritania', 'iso2' => 'mr'],
            ['name' => 'Mauritius', 'iso2' => 'mu'],
            ['name' => 'Mayotte', 'iso2' => 'yt'],
            ['name' => 'Mexico', 'iso2' => 'mx'],
            ['name' => 'Micronesia', 'iso2' => 'fm'],
            ['name' => 'Moldova', 'iso2' => 'md'],
            ['name' => 'Monaco', 'iso2' => 'mc'],
            ['name' => 'Mongolia', 'iso2' => 'mn'],
            ['name' => 'Montenegro', 'iso2' => 'me'],
            ['name' => 'Montserrat', 'iso2' => 'ms'],
            ['name' => 'Morocco', 'iso2' => 'ma'],
            ['name' => 'Mozambique', 'iso2' => 'mz'],
            ['name' => 'Myanmar (Burma)', 'iso2' => 'mm'],
            ['name' => 'Namibia', 'iso2' => 'na'],
            ['name' => 'Nauru', 'iso2' => 'nr'],
            ['name' => 'Nepal', 'iso2' => 'np'],
            ['name' => 'Netherlands', 'iso2' => 'nl'],
            ['name' => 'New Caledonia', 'iso2' => 'nc'],
            ['name' => 'New Zealand', 'iso2' => 'nz'],
            ['name' => 'Nicaragua', 'iso2' => 'ni'],
            ['name' => 'Niger', 'iso2' => 'ne'],
            ['name' => 'Nigeria', 'iso2' => 'ng'],
            ['name' => 'Niue', 'iso2' => 'nu'],
            ['name' => 'Norfolk Island', 'iso2' => 'nf'],
            ['name' => 'North Korea', 'iso2' => 'kp'],
            ['name' => 'North Macedonia', 'iso2' => 'mk'],
            ['name' => 'Northern Mariana Islands', 'iso2' => 'mp'],
            ['name' => 'Norway', 'iso2' => 'no'],
            ['name' => 'Oman', 'iso2' => 'om'],
            ['name' => 'Pakistan', 'iso2' => 'pk'],
            ['name' => 'Palau', 'iso2' => 'pw'],
            ['name' => 'Palestine', 'iso2' => 'ps'],
            ['name' => 'Panama', 'iso2' => 'pa'],
            ['name' => 'Papua New Guinea', 'iso2' => 'pg'],
            ['name' => 'Paraguay', 'iso2' => 'py'],
            ['name' => 'Peru', 'iso2' => 'pe'],
            ['name' => 'Philippines', 'iso2' => 'ph'],
            ['name' => 'Poland', 'iso2' => 'pl'],
            ['name' => 'Portugal', 'iso2' => 'pt'],
            ['name' => 'Puerto Rico', 'iso2' => 'pr'],
            ['name' => 'Qatar', 'iso2' => 'qa'],
            ['name' => 'Réunion', 'iso2' => 're'],
            ['name' => 'Romania', 'iso2' => 'ro'],
            ['name' => 'Russia', 'iso2' => 'ru'],
            ['name' => 'Rwanda', 'iso2' => 'rw'],
            ['name' => 'Samoa', 'iso2' => 'ws'],
            ['name' => 'San Marino', 'iso2' => 'sm'],
            ['name' => 'São Tomé & Príncipe', 'iso2' => 'st'],
            ['name' => 'Saudi Arabia', 'iso2' => 'sa'],
            ['name' => 'Senegal', 'iso2' => 'sn'],
            ['name' => 'Serbia', 'iso2' => 'rs'],
            ['name' => 'Seychelles', 'iso2' => 'sc'],
            ['name' => 'Sierra Leone', 'iso2' => 'sl'],
            ['name' => 'Singapore', 'iso2' => 'sg'],
            ['name' => 'Sint Maarten', 'iso2' => 'sx'],
            ['name' => 'Slovakia', 'iso2' => 'sk'],
            ['name' => 'Slovenia', 'iso2' => 'si'],
            ['name' => 'Solomon Islands', 'iso2' => 'sb'],
            ['name' => 'Somalia', 'iso2' => 'so'],
            ['name' => 'South Africa', 'iso2' => 'za'],
            ['name' => 'South Korea', 'iso2' => 'kr'],
            ['name' => 'South Sudan', 'iso2' => 'ss'],
            ['name' => 'Spain', 'iso2' => 'es'],
            ['name' => 'Sri Lanka', 'iso2' => 'lk'],
            ['name' => 'St. Barthélemy', 'iso2' => 'bl'],
            ['name' => 'St. Helena', 'iso2' => 'sh'],
            ['name' => 'St. Kitts & Nevis', 'iso2' => 'kn'],
            ['name' => 'St. Lucia', 'iso2' => 'lc'],
            ['name' => 'St. Martin', 'iso2' => 'mf'],
            ['name' => 'St. Pierre & Miquelon', 'iso2' => 'pm'],
            ['name' => 'St. Vincent & Grenadines', 'iso2' => 'vc'],
            ['name' => 'Sudan', 'iso2' => 'sd'],
            ['name' => 'Suriname', 'iso2' => 'sr'],
            ['name' => 'Svalbard & Jan Mayen', 'iso2' => 'sj'],
            ['name' => 'Sweden', 'iso2' => 'se'],
            ['name' => 'Switzerland', 'iso2' => 'ch'],
            ['name' => 'Syria', 'iso2' => 'sy'],
            ['name' => 'Taiwan', 'iso2' => 'tw'],
            ['name' => 'Tajikistan', 'iso2' => 'tj'],
            ['name' => 'Tanzania', 'iso2' => 'tz'],
            ['name' => 'Thailand', 'iso2' => 'th'],
            ['name' => 'Timor-Leste', 'iso2' => 'tl'],
            ['name' => 'Togo', 'iso2' => 'tg'],
            ['name' => 'Tokelau', 'iso2' => 'tk'],
            ['name' => 'Tonga', 'iso2' => 'to'],
            ['name' => 'Trinidad & Tobago', 'iso2' => 'tt'],
            ['name' => 'Tunisia', 'iso2' => 'tn'],
            ['name' => 'Türkiye', 'iso2' => 'tr'],
            ['name' => 'Turkmenistan', 'iso2' => 'tm'],
            ['name' => 'Turks & Caicos Islands', 'iso2' => 'tc'],
            ['name' => 'Tuvalu', 'iso2' => 'tv'],
            ['name' => 'U.S. Virgin Islands', 'iso2' => 'vi'],
            ['name' => 'Uganda', 'iso2' => 'ug'],
            ['name' => 'Ukraine', 'iso2' => 'ua'],
            ['name' => 'United Arab Emirates', 'iso2' => 'ae'],
            ['name' => 'United Kingdom', 'iso2' => 'gb'],
            ['name' => 'Uruguay', 'iso2' => 'uy'],
            ['name' => 'Uzbekistan', 'iso2' => 'uz'],
            ['name' => 'Vanuatu', 'iso2' => 'vu'],
            ['name' => 'Vatican City', 'iso2' => 'va'],
            ['name' => 'Venezuela', 'iso2' => 've'],
            ['name' => 'Vietnam', 'iso2' => 'vn'],
            ['name' => 'Wallis & Futuna', 'iso2' => 'wf'],
            ['name' => 'Western Sahara', 'iso2' => 'eh'],
            ['name' => 'Yemen', 'iso2' => 'ye'],
            ['name' => 'Zambia', 'iso2' => 'zm'],
            ['name' => 'Zimbabwe', 'iso2' => 'zw'],
        ];

        return array_column($countries, 'iso2');
    }

    /**
     * Check is the customer phone number is required.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isRequired(): bool
    {
        return (bool)$this->eavConfig
            ->getAttribute('customer_address', 'telephone')
            ->getIsRequired();
    }

    /**
     * Get path for script.
     *
     * @param string $fileId
     * @return string
     */
    private function getViewFileUrl($fileId)
    {
        return $this->assetRepository->getUrlWithParams(
            $fileId,
            ['_secure' => $this->request->isSecure()]
        );
    }
}
