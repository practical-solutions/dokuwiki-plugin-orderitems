<?php
/**
 * Plugin Search Form: Inserts a search form in any page
 *
 * @license    MIT
 * @author     Gero Gothe <practical@medizin-lernen.de>
 */
 

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_orderitems extends DokuWiki_Syntax_Plugin {

    /**
     * Syntax Type
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @return string
     */
    public function getType() {
        return 'formatting';
    }
    
    function getPType() { return 'normal'; }
    
    
    function getAllowedTypes() { return array('formatting','substition'); }
    
    function getSort() { return 136; }

    
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<orderitems>',$mode,'plugin_orderitems');
    }

   
    function postConnect() {      
        $this->Lexer->addExitPattern('</orderitems>', 'plugin_orderitems');
    }


    function handle($match, $state, $pos, Doku_Handler $handler){

        if ($state == DOKU_LEXER_UNMATCHED) {
            return $match;
        }
          
        return false;
    }


    public function build_form($lines){
    
        $mail = '';
        
        $passed = true;
        if (!isset($_POST['sendorder'])) $passed = false;

        $amount = 0;
        
        $res .= "<form name='orderitem' method='post' action=''><table class='plugin_orderitems' >";
            
        foreach ($lines as $l) {
            $l = trim($l);

            if (strlen($l)>0) {
        
                if (strpos(strtolower($l),'mail ') === 0) {
                    $mail = substr($l,5);
                    $res .= "<input type='hidden' name='email' value='$mail'>";
                } else {
                        
                    if (strpos(strtolower($l),'space') === 0) {
                        $res .= "</table><table class='plugin_orderitems'><br>";
                    } elseif (strpos(strtolower($l),'hidden') === 0) {
                        $amount++;
                        $res .= "<input type=\"hidden\" name=\"Zusatztext $amount\" value='".substr($l,6)."'>";
                    } else {
                        
                        
                            
                        $box = 0; # 0 = small textbox (default), 1 = large textbox, 2 = textarea
                        if (strpos(strtolower($l),'text ') === 0) {$l = substr($l,5);$box=1;}
                        if (strpos(strtolower($l),'box ') === 0) {$l = substr($l,4);$box=2;}
                        
                        // check if obligatory
                        $ob = false;
                        if (strrpos($l,"!!") == strlen($l)-2) {$ob = true;$l=substr($l,0,strlen($l)-2);}
                        
                        
                        $t = explode("##",$l);
                        $l = $t[0];
                        if (isset($t[1])) {$unit = $t[1];} else $unit = '';
                        if (isset($t[2])) {$value = $t[2];} else $value = '';
                            
                        
                            
                        $res .= "<tr>";
                            
                        $res .= "<td>$l".($ob? "*":"")."</td>";
                        $res .= "<td>";
                            
                        $red = false;
                        if (isset($_POST[str_replace(" ","_",$l)])) {
                            $value = $_POST[str_replace(" ","_",$l)];
                            if ($ob && $_POST[str_replace(" ","_",$l)]=='') {
                                msg('Please fill out "<b>'.$l.'</b>"',-1);
                                $red = true;
                                $passed = false;
                            }
                        }
                        
                        if ($box==2) {
                            $res .= '<textarea '.($red? " style='background-color:pink' ":"").' cols="50" rows="3" name="'.$l.'" >'.$value.'</textarea>';
                        } else {    
                            $res .= '<input autocomplete="off" '.($red? " style='background-color:pink' ":"").'type="text" size="'.($box == 0? "2":"40").'" name="'.$l.'" value="'.$value.'">';
                        }
                            
                        $res .= " $unit</td>";
                        
                        $res .= "</tr>";
                    }
                }
                
            }
        }
        
        
        if ($mail == false) {
            $passed = false; # email has to be stated
            msg('Invalid form: No E-Mail Adress stated.',-1);
        }
            
        $res .= "</table><input type='submit' value='".$this->getLang("send button")."' name='sendorder'></form>";    
        
        return Array("pass" => $passed, "form" => $res);
    }

    public function format_mail($info){
        $res = "<html><table border=1 style='border-collapse: collapse;'>";
        foreach ($info as $k => $i) {
            if (!in_array($k,Array('email','space','sendorder')) && $i<>"") {
                $k = str_replace("_"," ",$k);
                $res .= "<tr><th style='padding:2px 10px;background:linen'>$k</th><td style='padding:2px 10px;'>$i</td><tr>";
            }
            
        }
        $res.= "</table></html>";
        return $res;
    }

    public function render($format, Doku_Renderer $renderer, $data) {
        
        if ($data === false) return;
        
        if($format == 'xhtml') {
            $renderer->info['cache'] = false;
            
            $lines = explode("\n",$data);
            //print_r($_POST);
            
            $d = $this->build_form($lines);
            
            if (!$d['pass']) {
                $renderer->doc .= $d['form'];
            } else {
                $renderer->doc .= $this->getLang("send msg");
                
                if (isset($_POST['sendorder'])){
                    if (mail($_POST['email'],'Bestellung',$this->format_mail($_POST),"Content-type: text/html; charset=utf-8\r\n")) {
                        msg("E-Mail send.",1);
                    } else msg("Error: E-Mail could not be sent.",-1);
                }
            }

            return true;
        }
        
        return false;
    }
}