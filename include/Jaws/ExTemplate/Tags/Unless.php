<?php
/**
 * Class for tag unless
 * The opposite of if – executes a block of code only if a certain condition is not met
 *
 * @category    Template
 * @package     Core
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2020 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 * @doc         https://shopify.github.io/liquid/tags/control-flow/
 */
class Jaws_ExTemplate_Tags_Unless extends Jaws_ExTemplate_Tags_If
{
    protected function negateIfUnless($display)
    {
        return !$display;
    }

}