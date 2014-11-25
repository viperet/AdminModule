 	<?	
	require_once(PATH_ROOT."classes/pageSplit.class.php");

	class Pager extends pageSplit{
		var $body_current = "<label>{text}</label>";
		
    /**
     * pageSplit::pageSplit() - el constructor
     * 
     * @param $pointer
     * @param $max_elements
     * @param $toshow
     * @return 
     */
		function pageSplitComments($pointer,$max_elements,$link='',$max=''){			
			if(empty($max)){
				$max = COMPAGER;
			}
			
			//set variables
			$this->pageSplit($pointer*$max,$max_elements,$link,$max);
		}          
		
		function correctLink($page) {
			return ($page==1?"":"&page=$page");
		}
		
		/**
		 * pageSplit::showNav() - actual processing
		 * 
		 * @return 
		 */
		function showNav(){
/*
			$this->body = '<td><a href={link}>{text}</a></td>';
			$this->body_current = '<td><b>{text}</b></td>';
			$this->separator='';
			$this->header = '<table cellpadding="0" cellspacing="2" class="pagerp" align="center"><tr>';
			$this->footer = "</tr></table>";
*/			

			$this->setDesign('<div class="page"><a href="{link}">{text}</a></div>','<div class="page selected">{text}</div>','','' /* начало */ ,'' /* конец */);
			
			//initialize
			$elements = array();
		
			//find out total number of blocks
			$repetitions = ceil($this->max_elements / $this->block_size);
			//adjust them to the loops to fit the slider
/* 			if($repetitions<=$this->slider){ */
				//okay, the total number of elements is less than needed for a slider
				$start_loop = 0;
				$end_loop = $repetitions;
/*
			}else{
				//wow, we need to figure out how to slide through the elements
				
				//this is the mid point of the slider
				$mid_point = floor($this->pointer / $this->block_size);
				
				$my_step = $this->slider / 2; //step from the mid point
				$step_left = floor($my_step);	//step to the left
				$step_right = ceil($my_step);	//step to the right
				
				$start_loop = $mid_point - $step_left;	//starting block of data
				$end_loop = $mid_point + $step_right;		//ending block of data
				
				//error check, slider correction
				if($start_loop < 0){	//cannot be a negative block ;)
					$end_loop = $end_loop + abs($start_loop);	//if it is, add the excess to the end
					$start_loop = 0;
				}
				
				if($end_loop>$repetitions){	//cannot have more elements than there is!
					$start_loop = $start_loop - $end_loop + $repetitions;	//if it does, add to the beginning
					$end_loop = $repetitions;
				}						
			}
*/
	
      $out = "<div class='pager'>";     
/*
      if($this->pointer > 0) {
        // previous page
        $out .= "<a href='{$this->link}".$this->correctLink($this->pointer/$this->block_size)."' class='previous' style='width:121px; height:25px; margin:3px; display:block; background:url(http://banker.ua/site_img/pager_buttons.png) top left no-repeat;float:left;'></a>";
      }
      if(($this->pointer+$this->block_size) < $this->max_elements) {
        // next page
        $out .= "<a href='{$this->link}".$this->correctLink($this->pointer/$this->block_size+2)."' class='next' style='width:121px; height:25px; margin:3px; display:block; background:url(http://banker.ua/site_img/pager_buttons.png) 0 -25px no-repeat;float:left;'></a>";
      }
  
      $out .= "<a href='{$this->link}'  class='first' style='width:121px; height:25px; margin:3px; display:block; background:url(http://banker.ua/site_img/pager_buttons.png) 0 -50px no-repeat;float:right;'></a>";
*/
					
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output header
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			$out .= $this->header;
					//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output first
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			if($start_loop > 0){
				$temp = str_replace("{text}",1,$this->body);
				$out .= str_replace("{link}",$this->link,$temp) . "<td> ... </td>";
			}
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output contents
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			for($x=$start_loop;$x<$end_loop;$x++){
				$start_link = (($x - 1) * $this->block_size) + $this->block_size;
				$end_link = (($x - 1) * $this->block_size) + $this->block_size;
				
				if($end_link>$this->max_elements){
					$end_link = $this->max_elements;
				}
				
				//get the correct degign template
				if((($this->pointer + 1)>$start_link) and ($this->pointer<=$end_link)){
					//selected
					$temp = str_replace("{text}",$x+1,$this->body_current);
				}else{
					//possible to navigate to
					$temp = str_replace("{text}",$x+1,$this->body);
					$my_link = ($x + 1);
					$temp = str_replace("{link}",$this->link .$this->correctLink($my_link),$temp);
				}				
				$elements[$x] = $temp;
			}
			
			$out .= implode($this->separator,$elements);
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output last
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
			if($end_loop < $repetitions){
				$lastpagel = ceil($this->max_elements / $this->block_size);
				$temp = str_replace("{text}",$lastpagel,$this->body);
				$out .= "<td> ... </td>" . str_replace("{link}",$this->link . $this->correctLink($lastpagel),$temp);
			}
			
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO	
			//output footer
			//OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO			
			$out .= $this->footer;
			
      
      $out .= "</div>";

			
			return $out;
		}//end function
  }//end class pageSplit
?>
