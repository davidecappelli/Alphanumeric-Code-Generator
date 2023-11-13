<?php
class AlphanumericCodesGenerator {
    private static $prefix;                 // Prefix (Optional)
    private static $volume;                 // Number of generated strings
    private static $maxvolume   = 100001;
    private static $length;                 // Max length of each single string (including Prefix)
    private static $maxlength   = 16;
    private static $technique;              // Applied technique to generate code (default is custom())
    private static $techniques  = [
	        ['name' => 'custom()', 'callback' => 'custom', 'desc' => 'Randomizer (large volumes)'],
	        ['name' => 'md5()', 'callback' => 'md5', 'desc' => 'MD5'],
	        ['name' => 'sha1()', 'callback' => 'sha1', 'desc' => 'SHA1'],
	        ['name' => 'hex()', 'callback' => 'hex', 'desc' => 'Hexadecimal values (small volumes)'],
        ];
	private static $output;                 // Type of generated output
    private static $outputs = [
            ['name' => 'CSV', 'callback' => 'csv', 'desc' => 'Comma Separated Value File'],
	        ['name' => 'TBL', 'callback' => 'table', 'desc' => 'HTML Table'],
	        ['name' => 'LIST', 'callback' => 'list', 'desc' => 'HTML Ordered List'],
	        ['name' => 'TXT', 'callback' => 'text', 'desc' => 'HTML Textarea with spaces'],
        ];
    private static $case;                   //
    private static $cases   = [
            ['name' => 'Original', 'callback' => 'none'],
            ['name' => 'Lowercase', 'callback' => 'low'],
            ['name' => 'Uppercase', 'callback' => 'up'], // Forced capitalization of codes
        ];
    private static $checkFields = FALSE;
    private static $csvfilename = "code_generator.csv";
    private static $codes       = [];

    private static function techniques(){
        foreach(self::$techniques as $technique){
            ?>
            <option<?= isset($technique['desc']) ? ' title="'.$technique['desc'].'"' : ''; ?> value="<?= $technique['callback']; ?>"<?= $technique['callback'] == self::$technique ? ' selected' : ''; ?>><?= $technique['name']; ?></option>
            <?php
        }
    }

	private static function outputs(){
		foreach(self::$outputs as $output){
            ?>
            <option<?= isset($output['desc']) ? ' title="'.$output['desc'].'"' : ''; ?> value="<?= $output['callback']; ?>"<?= $output['callback'] == self::$output ? ' selected' : ''; ?>><?= $output['name']; ?></option>
			<?php
		}
	}

	private static function cases(){
		foreach(self::$cases as $case){
			?>
            <option value="<?= $case['callback']; ?>"<?= $case['callback'] == self::$case ? ' selected' : ''; ?>><?= $case['name']; ?></option>
			<?php
		}
	}

    private static function insertCode($string = NULL) : bool{
        if(!isset($string)) return FALSE;
	    $code   = self::$case == 'up' ? strtoupper($string) : (self::$case == 'low' ? strtolower($string) : $string); // Case Forcing
        if(in_array($code,self::$codes)){ // Code already present
            return FALSE;
        }else{
	        array_push(self::$codes,$code);
            return TRUE;
        }
    }

    private static function checkFields(){
	    /* Prefix Check */
        self::$prefix = (isset($_POST['prefix']) && ctype_alpha($_POST['prefix']) === TRUE) ? trim(preg_replace('/[^A-Za-z0-9]/','',$_POST['prefix'])) : NULL;

        /* Length Check */
        if(isset($_POST['length'])){
            $length         = intval($_POST['length']);
            self::$length   = $length <= self::$maxlength ? $length : self::$maxlength;
        }else{
	        self::$length = NULL;
        }

        /* Volume Check */
	    if(isset($_POST['volume'])){
		    $volume         = intval($_POST['volume']);
		    self::$volume   = $volume <= self::$maxvolume ? $volume : self::$maxvolume;
	    }else{
		    self::$volume = NULL;
	    }

        /* Technique Check */
	    self::$technique    = (isset($_POST['technique']) && in_array($_POST['technique'],array_column(self::$techniques,'callback'))) ? $_POST['technique'] : NULL;

        /* Output Check */
	    self::$output       = (isset($_POST['output']) && in_array($_POST['output'],array_column(self::$outputs,'callback'))) ? $_POST['output'] : NULL;

	    /* Case Check */
	    self::$case         = (isset($_POST['case']) && in_array($_POST['case'],array_column(self::$cases,'callback'))) ? $_POST['case'] : NULL;

        /* Check Results */
        self::$checkFields  = isset(self::$length,self::$volume,self::$technique,self::$output) ? TRUE : FALSE;
    }

	private static function custom($length = NULL) {
		if(!isset($length)) return FALSE;
		$chars  = '0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890123456789'; // Numbers repeated two times
		return substr(str_shuffle($chars), 0, $length);
	}

	private static function md5($length = NULL) {
		if(!isset($length)) return FALSE;
		return substr(md5(uniqid(rand(), TRUE)), 0, $length);
	}

	private static function sha1($length = NULL) {
		if(!isset($length)) return FALSE;
		return substr(sha1(uniqid(rand(), TRUE)), 0, $length);
	}

	private static function hex($length = NULL){
		if(!isset($length)) return FALSE;
		if (function_exists('random_bytes')) {
			$bytes = random_bytes(ceil($length / 2));
		}elseif (function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes(ceil($length / 2));
		} else {
			throw new Exception('No cryptographically secure random function found');
		}
		return substr(bin2hex($bytes), 0, $length);
	}

	private static function createCode(){
		if(self::$checkFields !== TRUE) return NULL;
		$chars  = self::$length - strlen(self::$prefix);
		$code   = call_user_func_array('self::'.self::$technique, [$chars]); // Call selected technique providing length for desired string
		if(!isset($code) || !preg_match('([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z])', $code)) return NULL; // Must contain at least one letter and one number
        return ctype_alpha(substr($code, 0, 1)) === TRUE ? self::$prefix.$code : NULL; // Not leading zero or any other number
	}

	public static function form(){
        ?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<ul>
                <li><label for="prefix">Prefix</label><input type="text" name="prefix" id="prefix" value="<?php echo ctype_alnum(self::$prefix) === TRUE ? self::$prefix : ''; ?>" /></li>
                <li><label for="length">Length</label><input required type="text" name="length" id="length" size="<?= strlen(self::$maxlength); ?>" value="<?php echo is_int(self::$length) ? self::$length : ''; ?>" /></li>
                <li><label for="volume">Volume</label><input required type="text" name="volume" id="volume" size="<?= strlen(self::$maxvolume); ?>" value="<?php echo is_int(self::$volume) ? self::$volume : ''; ?>" /></li>
                <li><label for="technique">Technique</label><select name="technique" id="technique"><?php self::techniques(); ?></select></li>
                <li><label for="case">Case</label><select name="case" id="case"><?php self::cases(); ?></select></li>
                <li><label for="output">Output</label><select name="output" id="output"><?php self::outputs(); ?></select></li>
                <li><input type="submit" /></li>
            </ul>
		</form>
		<?php
	}

    public static function page(){
        self::checkFields();
    		self::codes();
        if(self::$checkFields === TRUE && self::$output == 'csv'){
        self::csv();
        }
        ?>
    		<html>
        		<head>
              <style>
                  ul {list-style-type: none}
                  ul li {display:inline-block;margin-bottom: 15px;}
                  #outputfield {max-width:100%;margin:15px;overflow-y:scroll;overflow-x:hidden;height:408px;border:solid 1px #333;padding:5px;}
                  pre {white-space: pre-wrap;margin:0;padding:0;}
              </style>
            </head>
		        <body>
                <h2>Alphanumeric Code Generator</h2>
            		<?php echo self::form(); ?>
                <?php if(self::$checkFields === TRUE && self::$output != 'csv'): ?>
                <div id="output">
        	        <?= call_user_func('self::'.self::$output); ?>
                </div>
                <?php endif; ?>
        		</body>
    		</html>
  		<?php
  	}

    public static function codes(){
	    if(self::$checkFields !== TRUE || !is_int(self::$length) || !is_int(self::$volume)) return FALSE;
	    while(count(self::$codes) < self::$volume){
		    self::insertCode(self::createCode());
	    }
    }

    public static function table(){
        if(empty(self::$codes)) return FALSE;
        $output = '<table>'.PHP_EOL;
        foreach(self::$codes as $code){
            $output .= '<tr><td>'.$code.'</td></tr>'.PHP_EOL;
        }
	    $output .= '</table>'.PHP_EOL;
        return $output;
    }

	public static function list(){
		if(empty(self::$codes)) return FALSE;
		$output = '<ol>'.PHP_EOL;
		foreach(self::$codes as $code){
			$output .= '<li>'.$code.'</li>'.PHP_EOL;
		}
		$output .= '</li>'.PHP_EOL;
		return $output;
	}

	public static function text(){
		if(empty(self::$codes)) return FALSE;
		$output = '<div id="outputfield">'.PHP_EOL.'<pre>';
		foreach(self::$codes as $code){
			$output .= $code.' ';
		}
		$output .= '</pre>'.PHP_EOL.'</div>'.PHP_EOL;
		return $output;
	}

	public static function csv(){
		if(empty(self::$codes)) return FALSE;
        ob_start();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.self::$csvfilename);
		$header_args = ['Codes'];
		$data = array_map(function($code){ return [$code]; }, self::$codes);
		ob_end_clean();
		$output = fopen( 'php://output', 'w' );
		fputcsv($output, $header_args );
		foreach($data as $data_item){
			fputcsv($output,$data_item);
		}
		fclose( $output );
		exit;
	}

    public function __construct(){
        self::page();
    }

}

new AlphanumericCodesGenerator;
