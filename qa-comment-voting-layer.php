<?php

	class qa_html_theme_layer extends qa_html_theme_base {

		function option_default($option) {
			
			switch($option) {
				default:
					return false;
			}
			
		}

		// check for post
		
		function doctype()
		{
			if(!isset($_POST['ajax_comment_vote'])) qa_html_theme_base::doctype();
		}

		function html()
		{
			if(isset($_POST['ajax_comment_vote'])) $this->ajaxCommentVote($_POST['ajax_comment_vote'],$_POST['ajax_comment_vote_id']);
			else qa_html_theme_base::html();
		}

		function head_script() {
			qa_html_theme_base::head_script();
			$this->output("
<script>
	function ajaxCommentVote(elem, oldvote)
	{
		var ens=elem.getAttribute('name').split('_');
		var postid=ens[1];
		var vote=parseInt(ens[2]);
		var anchor=ens[3];
		var which=parseInt(ens[4]);

		var dataString = 'ajax_comment_vote_id='+postid+'&ajax_comment_vote='+vote;  

		jQuery.ajax({  
		  type: 'POST',  
		  url: '".qa_self_html()."',  
		  data: dataString,  
		  dataType: 'json',  
		  success: function(json) {
				if (json.status==1) {
					switch(vote) {
						case 1:
							var up = 0;
							var down = -1;
							var up_type = '-selected';
							var down_type = '-disabled';
							break;
						case -1:
							var up = 1;
							var down = 0;
							var up_type = '-disabled';
							var down_type = '-selected';
							break;
						case 0:
							var up = 1;
							var down = -1;
							var up_type = '';
							var down_type = '';
							break;
					}

					elem.parentNode.innerHTML = '<div class=\"comment-vote-item'+up_type+'\" name=\"vote_'+ens[1]+'_'+up+'_c'+ens[1]+'_1\" onclick=\"ajaxCommentVote(this);\">▲</div><div id=\"voting_'+ens[1]+'\">'+json.data+'</div><div class=\"comment-vote-item'+down_type+'\" name=\"vote_'+ens[1]+'_'+down+'_c'+ens[1]+'_-1\" onclick=\"ajaxCommentVote(this);\">▼</div>';

				} else if (json.status==0) {
					var mess=document.getElementById('errorbox');
					if (!mess) {
						var mess=document.createElement('div');
						mess.id='errorbox';
						mess.className='qa-error';
						mess.innerHTML=json.data;
					}
					var postelem=document.getElementById(anchor);
					postelem.parentNode.insertBefore(mess, postelem);
				} else {
					alert('Unexpected response from server - please try again.');
				}
			} 
		});
		return false;
	} 	
</script>");
		}
		function head_custom() {
			$this->logged_in_userid = qa_get_logged_in_userid();
			$this->comment_votes = $this->logged_in_userid?qa_db_read_all_assoc(qa_db_query_sub('SELECT ^uservotes.vote AS vote, ^uservotes.postid AS postid  FROM ^posts,^uservotes WHERE ^uservotes.vote<>0 AND ^uservotes.userid=# AND ^uservotes.postid=^posts.postid AND ^posts.type=$',$this->logged_in_userid, 'C')):null;
			
			qa_html_theme_base::head_custom();
			$this->output('
<style>
	.comment-vote-container {
		float: left;
		margin-right: 6px;
		color: silver;
		text-align: center;
	}
	.comment-vote-item:hover {
		color:black;
		cursor:pointer;
	}
	.comment-vote-item-selected {
		color:orange;
		cursor:pointer;
	}
	.comment-vote-item-disabled {
		display:none;
	}
</style>');

		}

	// theme replacement functions

		function c_item_main($c_item)
		{
			$vote=0;
			$flag=0;
			foreach($this->comment_votes as $vote) {
				if($vote['postid'] == $c_item['raw']['postid']) {
					$vote = (int)$vote['vote'];
					break;
				}
			}
			$netvotes = ($c_item['raw']['netvotes']!=0?$c_item['raw']['netvotes']:'');
			switch($vote) {
				case 1:
					$this->output('<div class="comment-vote-container"><div class="comment-vote-item-selected" name="vote_'.$c_item['raw']['postid'].'_0_c'.$c_item['raw']['postid'].'_1" onclick="ajaxCommentVote(this);">▲</div><div id="voting_'.$c_item['raw']['postid'].'">'.$netvotes.'</div><div class="comment-vote-item-disabled" onclick="ajaxCommentVote(this);" name="vote_'.$c_item['raw']['postid'].'_-1_c'.$c_item['raw']['postid'].'_-1">▼</div></div>');
					break;
				case -1:
					$this->output('<div class="comment-vote-container"><div class="comment-vote-item-disabled" name="vote_'.$c_item['raw']['postid'].'_1_c'.$c_item['raw']['postid'].'_1" onclick="ajaxCommentVote(this);">▲</div><div id="voting_'.$c_item['raw']['postid'].'">'.$netvotes.'</div><div class="comment-vote-item-selected" onclick="ajaxCommentVote(this);" name="vote_'.$c_item['raw']['postid'].'_0_c'.$c_item['raw']['postid'].'_-1">▼</div></div>');
					break;
				default:
					$this->output('<div class="comment-vote-container"><div class="comment-vote-item" name="vote_'.$c_item['raw']['postid'].'_1_c'.$c_item['raw']['postid'].'_1" onclick="ajaxCommentVote(this);">▲</div><div id="voting_'.$c_item['raw']['postid'].'">'.$netvotes.'</div><div class="comment-vote-item" onclick="ajaxCommentVote(this);" name="vote_'.$c_item['raw']['postid'].'_-1_c'.$c_item['raw']['postid'].'_-1">▼</div></div>');
					break;
			}
			qa_html_theme_base::c_item_main($c_item);
		}

	// db variable
	
		var $logged_in_userid;
		
		var $comment_votes;
		
	// worker

		function ajaxCommentVote($vote, $postid) {
			global $topage,$qa_login_userid,$qa_cookieid;
			
			$post=qa_db_select_with_pending(qa_db_full_post_selectspec($qa_login_userid, $postid));
			$voteerror = $this->comment_vote_error_html($post, $qa_login_userid, $topage);
			if ($voteerror===false) {
				require_once QA_INCLUDE_DIR.'qa-app-votes.php';
				qa_vote_set($post, $qa_login_userid, qa_get_logged_in_handle(), $qa_cookieid, $vote);
				
				$comment = qa_db_single_select(qa_db_full_post_selectspec(null, $postid));
				
				$votes = $comment['netvotes'];
				echo '{"status":"1","data":"'.$votes.'"}';
			} else {
				echo '{"status":"1","data":"'.$voteerror.'"}';
			}

		}
	
		// 'hacked' function
	
		function comment_vote_error_html($post, $userid, $topage)
	/*
		Check if $userid can vote on $post, on the page $topage.
		Return an HTML error to display if there was a problem, or false if it's OK.
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-app-users.php';

			if (
				is_array($post) &&
				qa_opt('voting_on_cs') &&
				( (!isset($post['userid'])) || (!isset($userid)) || ($post['userid']!=$userid) )
			) {
				
				switch (qa_user_permit_error('permit_vote_a', 'V')) { // for now we piggyback onto permit_vote_a
					case 'login':
						return qa_insert_login_links(qa_lang_html('main/vote_must_login'), $topage);
						break;
						
					case 'confirm':
						return qa_insert_login_links(qa_lang_html('main/vote_must_confirm'), $topage);
						break;
						
					case 'limit':
						return qa_lang_html('main/vote_limit');
						break;
						
					default:
						return qa_lang_html('users/no_permission');
						break;
						
					case false:
						return false;
				}
			
			} else
				return qa_lang_html('main/vote_not_allowed'); // voting option should not have been presented (but could happen due to options change)
		}
		

	}

