<?php
namespace KHVT\AdminThemeManager\Core;

use OxidEsales\Eshop\Core\Registry;

class Config extends Config_parent
{
    /**
     * Finds and returns files or folders path in out dir
     *
     * @param string $file       File name
     * @param string $dir        Directory name
     * @param bool   $admin      Whether to force admin
     * @param int    $lang       Language id
     * @param int    $shop       Shop id
     * @param string $theme      Theme name
     * @param bool   $absolute   mode - absolute/relative path
     * @param bool   $ignoreCust Ignore custom theme
     *
     * @return string
     */
    public function getDir($file, $dir, $admin, $lang = null, $shop = null, $theme = null, $absolute = true, $ignoreCust = false)
    {
        if (is_null($theme)) {
            $theme = $this->getConfigParam('sTheme');
        }

        if ($admin) {
            $theme = 'admin';
        }

        if ($dir != $this->_sTemplateDir) {
            $base = $this->getOutDir($absolute);
            $absBase = $this->getOutDir();
        } else {
            $base = $this->getViewsDir($absolute);
            $absBase = $this->getViewsDir();
        }

        $langAbbr = '-';
        // false means skip language folder check
        if ($lang !== false) {
            $language = Registry::getLang();

            if (is_null($lang)) {
                $lang = $language->getEditLanguage();
            }

            $langAbbr = $language->getLanguageAbbr($lang);
        }

        if (is_null($shop)) {
            $shop = $this->getShopId();
        }

        //Load from
        $path = "{$theme}/{$shop}/{$langAbbr}/{$dir}/{$file}";
        $cacheKey = $path . "_{$ignoreCust}{$absolute}";

        if (($return = Registry::getUtils()->fromStaticCache($cacheKey)) !== null) {
            return $return;
        }

        $return = $this->getEditionTemplate("{$theme}/{$dir}/{$file}");

        // Check for custom template
        $customTheme = $this->getConfigParam('sCustomTheme');
        if (!$return && !$admin && !$ignoreCust && $customTheme && $customTheme != $theme) {
            $return = $this->getDir($file, $dir, $admin, $lang, $shop, $customTheme, $absolute);
        }

        //test lang level ..
        if (!$return && !$admin && is_readable($absBase . $path)) {
            $return = $base . $path;
        }

        //test shop level ..
        if (!$return && !$admin) {
            $return = $this->getShopLevelDir($base, $absBase, $file, $dir, $admin, $lang, $shop, $theme, $absolute, $ignoreCust);
        }

        //test theme language level ..
        $path = "$theme/$langAbbr/$dir/$file";
        if (!$return && $lang !== false && is_readable($absBase . $path)) {
            $return = $base . $path;
        }

        //test theme level ..
        $path = "$theme/$dir/$file";
        if (!$return && is_readable($absBase . $path)) {
            $return = $base . $path;
        }

        //test out language level ..
        $path = "$langAbbr/$dir/$file";
        if (!$return && $lang !== false && is_readable($absBase . $path)) {
            $return = $base . $path;
        }

        //test out level ..
        $path = "$dir/$file";
        if (!$return && is_readable($absBase . $path)) {
            $return = $base . $path;
        }

        // TODO: implement logic to log missing paths

        // to cache
        Registry::getUtils()->toStaticCache($cacheKey, $return);

        return $return;
    }
}