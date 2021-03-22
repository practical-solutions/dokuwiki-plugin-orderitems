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
        
        // echo "<pre>".print_r($_POST,true)."</pre>";

        $amount = 0;        # Counter for hidden elements
        $individual = 0;    # Counter for items which are named by the user
        
        $res .= "<form name='orderitem' method='post' action=''><table class='plugin_orderitems' >";
        
        foreach ($lines as $l) {
            $l = trim($l);

            if (strlen($l)>0) {
        
                if (strpos(strtolower($l),'mail ') === 0) {
                    $mail = substr($l,5);
                    $res .= "<input type='hidden' name='email' value='$mail'>";
                } else {
                    
                    if (strpos(strtolower($l),'space') === 0) {
                        $title = '';
                        if (strlen(trim($l))>5) $title = "<h3>".trim(substr($l,5))."</h3>";
                        $res .= "</table><br>$title<table class='plugin_orderitems'>";
                    } elseif (strpos(strtolower($l),'hidden') === 0) {
                        $amount++;
                        $res .= "<input type=\"hidden\" name=\"Zusatztext $amount\" value='".substr($l,6)."'>";
                    } else {
                                                
                        # Item name must be stated by the user
                        $noname = false;
                        
                        if (strpos(strtolower($l),'noname') === 0) {$noname = true;$l = ltrim(substr($l,6));}
                        // msg(strtolower($l).":".strpos(strtolower($l),'noname '));
                        
                        # Should item id be entered as an additional field?
                        $id = false;
                        if (strpos(strtolower($l),'id ') === 0 || strtolower($l)=='id') {$id = true;$l = ltrim(substr($l,2));}
                                               
                        $box = 0; # 0 = small textbox (default), 1 = large textbox, 2 = textarea
                        if (strpos(strtolower($l),'text ') === 0) {$l = substr($l,5);$box=1;}
                        if (strpos(strtolower($l),'box ') === 0) {$l = substr($l,4);$box=2;}
                        
                        if (strpos(strtolower($l),'copyto') === 0) {$box=1;}
                        
                        # check if obligatory
                        $ob = false;
                        if (strrpos($l,"!!") == strlen($l)-2) {$ob = true;$l=substr($l,0,strlen($l)-2);}
                        
                        
                        $t = explode("##",$l);
                        $l = trim($t[0]);
                        if (isset($t[1])) {$unit = trim($t[1]);} else $unit = '';
                        if (isset($t[2])) {$value = trim($t[2]);} else $value = '';
                        
                        $res .= "<tr>";
                        
                        if ($noname) {
                            
                            $individual++;
                            $l = "item$individual";
                            
                            # The item name is always obligatory, if a number has been entered
                            $tc = '';$tv='';
                            if (
                                isset($_POST['item'.$individual.'_name']) && 
                                $_POST['item'.$individual.'_name'] == '' &&
                                $_POST['item'.$individual.($unit==''? '':"_($unit)")] <> ''
                                ) {
                                $passed = false;
                                msg($this->getLang('item missing msg'),-1);
                                $tc = ' style="background-color:pink" ';
                            } else $tv = $_POST['item'.$individual.'_name'];
                            
                            $res .= '<td><input autocomplete="off" '.$tc.'type="text" size="30" name="item'.$individual.'_name" placeholder="'.$this->getLang('item name').'" value="'.$tv.'"></td>';
                        } else {
                            $n = $l;
                            if ($l == 'copyto') $n = $this->getLang('copy to');
                            $res .= "<td>$n".($ob? "*":"")."</td>";
                        }

                        $res .= "<td>";
                            
                        $red = false;
                        if (isset($_POST["sendorder"])) {
                            
                            $identifier = str_replace(" ","_",$l.($unit==''? '':"_($unit)"));
                            
                            $value = $_POST[$identifier];
                                                        
                            if ($ob && $value=='') {
                                msg($this->getLang('fill out msg').' "<b>'.$l.'</b>"',-1);
                                $red = true;
                                $passed = false;
                            }
                        }
                        
                        if ($box==2) {
                            $res .= '<textarea '.($red? " style='background-color:pink' ":"").' cols="50" rows="3" name="'.$l.($unit==''? '':" ($unit)").'" >'.$value.'</textarea>';
                        } else {    
                            $res .= '<input autocomplete="off" '.($red? " style='background-color:pink' ":"").'type="text" size="'.($box == 0? "2":"40").'" name="'.$l.($unit==''? '':" ($unit)").'" value="'.$value.'">';
                        }
                            
                        $res .= " $unit";
                        
                        if ($id) {
                            $res .= '&nbsp;&nbsp;&nbsp; Bestellnummer: <input type="text" autocomplete="off" size="20" name="'.$l.'_'.$this->getLang("item id").'" value="'.$_POST[$l.'_'.$this->getLang("item id")].'">';
                       }
                        
                        $res .= "</td>";
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
        
        $name = ''; #Array(); # Array for articles, which the user has named
        
        foreach ($info as $k => $i) {
            if (strpos($k,'item')==0 && strpos($k,'name')==strlen($k)-4) {
                $name = $i;
                continue;
            }
            if (strpos($k,'item')===0) {
                $f = strpos($k,'_');
                if ($f === false) {$k = $name;} else {$k = $name . substr($k,$f);}
            }
            
            if (!in_array($k,Array('email','space','sendorder','copyto')) && $i<>"") {
                $k = str_replace("_"," ",$k);
                $res .= "<tr><th style='padding:2px 10px;background:linen'>$k</th><td style='padding:2px 10px;'>$i</td></tr>";
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
                if (isset($_POST['sendorder'])){
                                  
                    global $ID;
                    
                    $mail = new Mailer();
                    $mail->to($_POST['email']);
                    $mail->subject($this->getLang("mail subject").': '.p_get_first_heading($ID));

                    $html = $this->format_mail($_POST);
        
                    $mail->setBody("",null,null,$html);

                    $ok = $mail->send();
                    
                    # Send a copy of the email
                    if (isset($_POST['copyto'])){
                        if (filter_var($_POST['copyto'], FILTER_VALIDATE_EMAIL)){                            
                            $mail->to($_POST['copyto']);
                            if ($mail->send()){
                                msg($this->getLang('copy to').$_POST['copyto'].' - '.$this->getLang('mail success'),1); 
                            } else msg($this->getLang('copy to').$_POST['copyto'].' - '.$this->getLang('mail error'),-1); 
                        } elseif ($_POST['copto']<>'') msg($this->getLang('copy to').$_POST['copyto'].' - '.$this->getLang('mail error'),-1); 
                    }
                    
                    if ($ok) {
                        msg($this->getLang("mail success"),1);
                        $renderer->doc .= '<div class="orderitems__successbox">'.$this->getLang("send msg").'</div>';
                    } else {
                        msg($this->getLang("mail error"),-1);
                        $renderer->doc .= $d['form'];
                    }
                    
                    # Show Table in PopUp with option to print
                    $renderer->doc .= '<div id="orderitems__print" style="display:none">'.
                                       date("d.m.Y").'<hr>'.
                                       $this->format_mail($_POST).
                                       '</div>';
                    $out = str_replace(array('%%SHOWPRINT%%','%%PRINT%%','%%CLOSE%%'),array($this->getLang("print show button"),$this->getLang("print button"),$this->getLang("close button")),file_get_contents('lib/plugins/orderitems/print.html'));
                    $renderer->doc .= $out;
                }
            }
            return true;
        }
        return false;
    }
}
