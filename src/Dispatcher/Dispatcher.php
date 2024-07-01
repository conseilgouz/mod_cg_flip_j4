<?php
/**
 * @package CG Flip Module
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2024 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz
 */

namespace ConseilGouz\Module\CGFlip\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

\defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_cg_flip
 *
 * @since  4.2.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   4.2.0
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        return $data;
    }
}
