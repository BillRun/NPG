<?php
/**
 * @copyright       Copyright (C) 2012-2013 S.D.O.C. LTD. All rights reserved.
 * @license         GNU Affero Public License version 3 or later; see LICENSE.txt
 */
class Zend_View_Helper_Pagination extends Zend_View_Helper_Abstract {

	public function pagination($limit = FALSE, $adjacents = FALSE, $page = FALSE , $total_pages = FALSE , $targetpage = FALSE) {
		if (!isset($page) || $page == 0)
			$page = 1;  //if no page var is given, default to 1.
		$prev = $page - 1;	//previous page is page - 1
		$next = $page + 1;	//next page is page + 1
//var_dump($limit);
//die;
		$lastpage = ceil($total_pages / $limit);  //lastpage is = total pages / items per page, rounded up.
		$lpm1 = $lastpage - 1;   //last page minus 1

		/*
		  Now we apply our rules and draw the pagination object.
		  We're actually saving the code to a variable in case we want to draw it more than once.
		 */
		$pagination = "";
		if ($lastpage > 1) {
			$pagination .= "<div class=\"pagination\">";
			//previous button
			if ($page > 1)
				$pagination.= "<a href=\"$targetpage?page=$prev\">previous </a><br/>";
			else
				$pagination.= "<span class=\"disabled\">previous </span><br/>";

			//pages	
			if ($lastpage < 7 + ($adjacents * 2)) { //not enough pages to bother breaking it up
				for ($counter = 1; $counter <= $lastpage; $counter++) {
					if ($counter == $page)
						$pagination.= "<span class=\"current\">$counter</span>";
					else
						$pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
				}
			}
			elseif ($lastpage > 5 + ($adjacents * 2)) { //enough pages to hide some
				//close to beginning; only hide later pages
				if ($page < 1 + ($adjacents * 2)) {
					for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
						if ($counter == $page)
							$pagination.= "<span class=\"current\">$counter</span>";
						else
							$pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
					$pagination.= "...";
					$pagination.= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
					$pagination.= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
				}
				//in middle; hide some front and some back
				elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
					$pagination.= "<a href=\"$targetpage?page=1\">1</a>";
					$pagination.= "<a href=\"$targetpage?page=2\">2</a>";
					$pagination.= "...";
					for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
						if ($counter == $page)
							$pagination.= "<span class=\"current\">$counter</span>";
						else
							$pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
					$pagination.= "...";
					$pagination.= "<a href=\"$targetpage?page=$lpm1\">$lpm1</a>";
					$pagination.= "<a href=\"$targetpage?page=$lastpage\">$lastpage</a>";
				}
				//close to end; only hide early pages
				else {
					$pagination.= "<a href=\"$targetpage?page=1\">1</a>";
					$pagination.= "<a href=\"$targetpage?page=2\">2</a>";
					$pagination.= "...";
					for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
						if ($counter == $page)
							$pagination.= "<span class=\"current\">$counter</span>";
						else
							$pagination.= "<a href=\"$targetpage?page=$counter\">$counter</a>";
					}
				}
			}

			//next button
//	var_dump($page);
//	die;
			if ($page < $counter - 1)
				$pagination.= "<br/><a href=\"$targetpage?page=$next\"> next</a>";
			else
				$pagination.= "<span class=\"disabled\"><br/> next</span>";
			$pagination.= "</div>\n";
		}
		return $pagination;
	}

}

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

