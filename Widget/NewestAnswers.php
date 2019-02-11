<?php

namespace Shriker\Faq\Widget;

use XF\Widget\AbstractWidget;

class NewestAnswers extends AbstractWidget
{
    protected $defaultOptions = [
		  'limit' => 5
    ];
    
    public function render()
    {
      $visitor = \XF::visitor();

      $options = $this->options;
      $limit = $options['limit'];
      $filter = $options['filter'];

      if (!$visitor->user_id)
      {
        $filter = 'latest';
      }
      
      $viewParams = [
        'title' => $this->getTitle() ?: $title,
        'link' => $link,
        'threads' => $threads,
        'style' => $options['style'],
        'filter' => $filter,
        'hasMore' => $total > $threads->count()
      ];
      return $this->renderer('widget_faq_newest_answers', $viewParams);
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