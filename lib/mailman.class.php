<?php
/*

Mailman (PHP Class) v2.0

@see http://wiki.list.org/pages/viewpage.action?pageId=4030567
*/

class Mailman {
	var $adminurl='http://www.example.co.uk/mailman/admin';
	var $list=false;
	var $adminpw=false;
	var $html;
	var $error=false;

	function Mailman ($adminurl,$list=false,$adminpw=false) {
		$this->adminurl=$adminurl;
		$this->list=$list;
		$this->adminpw=$adminpw;
	}
	function fetch ($url) {
		return file_get_contents($url);
	}
	/*List lists: 
	<domain.com>/mailman/admin*/
	function lists ($assoc=true) {
		$html=$this->fetch($this->adminurl);
		$match='#<tr.*?>\s+<td><a href="(.+?)"><strong>(.+?)</strong></a></td>\s+<td><em>(.+?)</em></td>\s+</tr>#i';
		$a=array();
		if (preg_match_all($match,$html,$m)) {
			foreach ($m[0] as $k => $v) {
				$a[$k][]=$m[1][$k];
				$a[$k][]=$m[2][$k];
				$a[$k][]=$m[3][$k];
				if ($assoc) {
					$a[$k]['path']=basename($m[1][$k]);
					$a[$k]['name']=$m[2][$k];
					$a[$k]['desc']=$m[3][$k];
				}
			}
		}
		return $a;
	}
	/*List a member:
	<domain.com>/mailman/admin/<listname>/members?findmember=<email-address>&setmemberopts_btn&adminpw=<adminpassword>*/
	function member ($email) {
		$path='/%s/members?findmember=%s&setmemberopts_btn&adminpw=%s';
		$path=sprintf($path,$this->list,$email,$this->adminpw);
		$url=$this->adminurl.$path;
		$html=$this->fetch($url);
		//TODO:parse html
		return $html;
	}
	/*Unsubscribe: 
	<domain.com>/mailman/admin/<listname>/members/remove?send_unsub_ack_to_this_batch=0&send_unsub_notifications_to_list_owner=0&unsubscribees_upload=<email-address>&adminpw=<adminpassword>*/
	function unsubscribe ($email) {
		$path='/%s/members/remove?send_unsub_ack_to_this_batch=0&send_unsub_notifications_to_list_owner=0&unsubscribees_upload=%s&adminpw=%s';
		$path=sprintf($path,$this->list,$email,$this->adminpw);
		$url=$this->adminurl.$path;
		$html=$this->fetch($url);
		if (preg_match('#<h5>Successfully Unsubscribed:</h5>#i',$html)) {
			$this->error=false;
			return true;
		} else {
			preg_match('#<h3>(.+?)</h3>#i',$html,$m);
			$this->error=trim(strip_tags($m[1]),':');
			return false;
		}
		$this->error=true;
		return false;
	}
	/*Subscribe: 
	<domain.com>/mailman/admin/<listname>/members/add?subscribe_or_invite=0&send_welcome_msg_to_this_batch=0&notification_to_list_owner=0&subscribees_upload=<email-address>&adminpw=<adminpassword>*/
	function subscribe ($email,$invite=0) {
		$path='/%s/members/add?subscribe_or_invite=%d&send_welcome_msg_to_this_batch=0&notification_to_list_owner=0&subscribees_upload=%s&adminpw=%s';
		$path=sprintf($path,$this->list,(int)$invite,$email,$this->adminpw);
		$url=$this->adminurl.$path;
		$html=$this->fetch($url);
		if (preg_match('#<h5>Successfully subscribed:</h5>#i',$html)) {
			$this->error=false;
			return true;
		} else {
			preg_match('#<h5>(.+?)</h5>#i',$html,$m);
			$this->error=trim(strip_tags($m[1]),':');
			return false;
		}
		$this->error=true;
		return false;
	}
	/*Set digest (you have to first subscribe them using URL above, then set digest):
	<domain.com>/mailman/admin/<listname>/members?user=<email-address>&<email-address>_digest=1&setmemberopts_btn=Submit%20Your%20Changes&allmodbit_val=0&<email-address>_language=en&<email-address>_nodupes=1&adminpw=<adminpassword>*/
	function setdigest ($email) {
		$path='/%s/members?user=
		%s&%s_digest=1&setmemberopts_btn=Submit%20Your%20Changes&allmodbit_val=0&%s_language=en&%s_nodupes=1&adminpw=%s';
		$path=sprintf($path,$this->list,$email,$email,$email,$email,$this->adminpw);
		$url=$this->adminurl.$path;
		$html=$this->fetch($url);
		//TODO:parse html
		return $html;
	}
	//List members
	function members () {
		//get the letters
		$url=$this->adminurl.sprintf('/%s/members?adminpw=%s',$this->list,$this->adminpw);
		$html=$this->fetch($url);
		$p='#<a href=".*?letter=(.)">.+?</a>#i';
		preg_match_all($p,$html,$m);
		$letters=array_pop($m);
		//do the loop
		$members=array(array(),array());
		foreach($letters as $letter) {
			$url=$this->adminurl.sprintf('/%s/members?letter=%s&adminpw=%s',$this->list,$letter,$this->adminpw);
			$html=$this->fetch($url);
			//parse html
			//$p='#<INPUT name="user" type="HIDDEN" value="(.+?)" >#i';
			$p='#<td><a href=".+?">(.+?)</a><br><INPUT name=".+?_realname" type="TEXT" value="(.*?)" size="\d{2}" ><INPUT name="user" type="HIDDEN" value=".+?" ></td>#i';
			preg_match_all($p,$html,$m);
			array_shift($m);
			$members[0]=array_merge($members[0],$m[0]);
			$members[1]=array_merge($members[1],$m[1]);
		}
		return $members;
	}
	//Version
	function version() {
		$url=$this->adminurl.sprintf('/%s/?adminpw=%s',$this->list,$this->adminpw);
		$html=$this->fetch($url);
		$p='#<td><img src="/img-sys/mailman.jpg" alt="Delivered by Mailman" border=0><br>version (.+?)</td>#i';
		preg_match($p,$html,$m);
		return array_pop($m);
	}
}//end

//eof