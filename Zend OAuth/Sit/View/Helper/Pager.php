<?php 

/**
 * View helper for generating a results pager
 **/

class Sit_View_Helper_Pager extends Zend_View_Helper_Abstract {
	
	private $baseUrl;
	
	/**
	 * @param String $baseUrl base url to user for pager links
	 * @param Doctrine_Pager $doctrinePager instance of Doctrine pager to render
	 * @param Object $params additional parameters to append to link
	 **/

	public function pager($baseUrl, $doctrinePager, $params = null) {
		$this->baseUrl = $baseUrl;	
		$result = '<ul class="pager">';
		
		if ($doctrinePager->haveToPaginate()) {
			
			$current = $doctrinePager->getPage();
			$first = $doctrinePager->getFirstPage();
			$last = $doctrinePager->getLastPage();
			$prev = $doctrinePager->getPreviousPage();
			$next = $doctrinePager->getNextPage();
			$range = $doctrinePager->getRange('Sliding', array('chunk' => 5))->rangeAroundPage();
			
			
			// Don't display first link if #1 is in range
			if (!in_array($first, $range)
				&& ($current != $first) 
				&& ($first != $prev)) {
				$result .= $this->page($first, '&lt;&lt; First', $params);
			}

			// Don't display < Prev if we are on #1
			if ($current != $prev) {
				$result .= $this->page($prev, '&lt; Prev', $params);
			}
			
			// render range of pages
			foreach ($range as $page) {
				if ($page == $current) {
					$result .= '<li>' . $page . '</li>'; 
				}
				else {
					$result .= $this->page($page, $page, $params);
				}
			}

			// Don't display next if we are at the last item
			if ($current != $next) {
				$result .= $this->page($next, 'Next &gt;', $params);
			}
			// Don't display last if we are at the last item
			if (!in_array($last, $range)
				&& ($current != $last) 
				&& ($last != $next)) {
				$result .= $this->page($last, 'Last &gt;&gt;', $params);
			}
		}		
		$result .= '<li class="resultCount">' . $doctrinePager->getFirstIndice() . '-' 
					. $doctrinePager->getLastIndice() . ' of ' . $doctrinePager->getNumResults() . ' </li>';
		$result .= '</ul>';
		return $result;
	}
	
	/**
	 * Create a page link
	 * @param Number $id index id of page
	 * @param String $label label of link
	 * @param Object $params additional parameters to append to link
	 **/
	private function page($id, $label, $params = null) {
		$getString = '';
		if ($params) {
			$getString = '?';
			foreach ($params as $key=>$value) {
				if ($getString != '?') {
					$getString .= '&';
				}
				$getString .= urlencode($key) . '=' . urlencode($value);
			}
		}
		return '<li><a href="' . $this->baseUrl . $id . $getString . '">' . $label . '</a></li>';
	}
	
}
