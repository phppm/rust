<?php
namespace rust\util\doc;
/**
 * Parses the PHPDoc comments for metadata. Inspired by Documentor code base
 *
 * @category   Framework
 * @package    restler
 * @subpackage helper
 * @author     Murray Picton <info@murraypicton.com>
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 * @link       https://github.com/murraypicton/Doqumentor
 */
class PHPDocParser {
    private static $params = [];

    public static function parse($doc = '') {
        if ($doc == '') {
            return static::$params;
        }
        // Get the comment
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === FALSE) {
            return static::$params;
        }
        $comment = trim($comment [1]);
        // Get all the lines and strip the * from the first character
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === FALSE) {
            return static::$params;
        }
        static::parseLines($lines [1]);
        return static::$params;
    }

    private static function parseLines($lines) {
        $desc = NULL;
        foreach ($lines as $line) {
            $parsedLine = static::parseLine($line); // Parse the line
            if (FALSE === $parsedLine && !isset (static::$params ['description'])) {
                if ($desc && is_array($desc)) {
                    // Store the first line in the short description
                    static::$params ['description'] = implode(PHP_EOL, $desc);
                }
                $desc = [];
            } elseif ($parsedLine !== FALSE) {
                $desc [] = $parsedLine; // Store the line in the long description
            }
        }
        $desc = implode(' ', $desc);
        if (!empty ($desc)) {
            static::$params ['long_description'] = $desc;
        }
    }

    /**
     * @param string $line
     *
     * @return bool|string
     */
    private static function parseLine($line) {
        $line = trim($line);
        if (empty ($line)) {
            return FALSE;
        } // Empty line
        if (strpos($line, '@') === 0) {
            if (strpos($line, ' ') > 0) {
                // Get the parameter name
                $param = substr($line, 1, strpos($line, ' ') - 1);
                $value = substr($line, strlen($param) + 2); // Get the value
            } else {
                $param = substr($line, 1);
                $value = '';
            }
            // Parse the line and return false if the parameter is valid
            if (static::setParam($param, $value)) {
                return FALSE;
            }
        }
        return $line;
    }

    /**
     * @param string $param
     * @param string $value
     *
     * @return bool
     */
    private static function setParam($param, $value) {
        if ($param == 'param' || $param == 'return') {
            $value = static::formatParamOrReturn($value);
        }
        if ($param == 'class') {
            list ($param, $value) = static::formatClass($value);
        }
        if (empty (static::$params [$param])) {
            static::$params [$param] = $value;
        } else {
            if ($param == 'param') {
                $arr = [
                    static::$params [$param],
                    $value,
                ];
                static::$params [$param] = $arr;
            } else {
                static::$params [$param] = $value + static::$params [$param];
            }
        }
        return TRUE;
    }

    private static function formatClass($value) {
        $r = preg_split("[|]", $value);
        if (is_array($r)) {
            $param = $r [0];
            parse_str($r [1], $value);
            foreach ($value as $key => $val) {
                $val = explode(',', $val);
                if (count($val) > 1) {
                    $value [$key] = $val;
                }
            }
        } else {
            $param = 'Unknown';
        }
        return [
            $param,
            $value,
        ];
    }

    private static function formatParamOrReturn($string) {
        $pos = strpos($string, ' ');
        $type = substr($string, 0, $pos);
        return '(' . $type . ')' . substr($string, $pos + 1);
    }
}