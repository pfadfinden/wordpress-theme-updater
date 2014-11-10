<?php

namespace Pfadfinden\WordPress\Bootstrap;

use Shy\WordPress\Feature;



/**
 * Feature that activates a little more HTML5 than WordPress itself does:
 *  - Disallow &lt;acronym&gt; tag in comments.
 */
class Html5Feature extends Feature
{
	public function __construct()
	{
		$this->addHookMethod( 'preprocess_comment',          'disallowAcronymTag' );
		$this->addHookMethod( 'comment_form_default_fields', 'disallowAcronymTag' );

		parent::__construct();
	}

	/**
	 * Removes &lt;acronym&gt; from the list of allowed tags.
	 * 
	 * Although unused in our method, $data is returned to satisfy the filter contract.
	 * 
	 * @param mixed $data
	 * @return mixed
	 */
	public function disallowAcronymTag( $data )
	{
		global $allowedtags;
		if ( isset( $allowedtags['acronym'] ) ) {
			unset( $allowedtags['acronym'] );
		}

		return $data;
	}
}
