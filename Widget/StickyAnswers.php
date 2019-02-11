<?php

namespace Shriker\Faq\Widget;

use XF\Widget\AbstractWidget;

class StickyAnswers extends AbstractWidget
{
    protected $defaultOptions = [
		'limit' => 5
    ];

    public function render()
    {
        
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
			'limit' => 'uint'
		]);
		if ($options['limit'] < 1)
		{
			$options['limit'] = 1;
		}

		return true;
    }
}