<?php
namespace rust\util;

class XSSClean {
    /**
     * Clean cross site scripting exploits from string.
     * HTMLPurifier may be used if installed, otherwise defaults to built in method.
     * Note - This function should only be used to deal with data upon submission.
     * It's not something that should be used for general runtime processing
     * since it requires a fair amount of processing overhead.
     *
     * @param   string|array $data Data to clean
     * @param   string $tool xss_clean method to use ('htmlpurifier' or defaults to built-in method)
     * @return  string
     */
    public static function doClean($data, $tool = NULL) {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = self::doClean($val, $tool);
            }
            return $data;
        }
        if ('' === trim($data)) {
            return $data;
        }
        if (is_bool($tool)) {
            $tool = 'default';
        } elseif (!method_exists(self, $tool . 'Filter')) {
            $tool = 'default';
        }
        $method = $tool . 'Filter';
        return self::$method($data);
    }

    /**
     * Default built-in cross site scripting filter.
     *
     * @param   string $data Data to clean
     * @return  string
     */
    protected static function defaultFilter($data) {
        // Fix &entity\n;
        $data = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(?:on[a-z]+|xmlns)\s*=\s*[\'"\x00-\x20]?[^\'>"]*[\'"\x00-\x20]?\s?#iu', '', $data);
        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#is', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#is', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#ius', '$1>', $data);
        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#<[\x00-\x20]*/*[\x00-\x20]*+(?:applet|b(?:ase|gsound|link)|embed|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+#i', '', $data);
        }
        while ($old_data !== $data);

        return $data;
    }
}