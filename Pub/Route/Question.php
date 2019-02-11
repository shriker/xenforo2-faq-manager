<?php

namespace Shriker\Faq\Pub\Route;

class Question
{
    public static function build(&$prefix, array &$route, &$action, &$data, array &$params, \XF\Mvc\Router $router)
	{
		if ($params || $action)
		{
			return null;
		}

		if ($data && !empty($data['faq_id']) && empty($data['depth']))
		{
			$route = 'help/faq/';

            $title = $router->prepareStringForUrl($data['question'], true) . '.' . intval($data['faq_id']);
            
			return new \XF\Mvc\RouteBuiltLink(
				$router->buildLink('nopath:' . $route) . $title
			);
		}

		return null;
	}
}