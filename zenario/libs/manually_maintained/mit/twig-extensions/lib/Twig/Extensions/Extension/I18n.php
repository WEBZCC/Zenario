<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Extensions_Extension_I18n extends Twig\Extension\AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(new Twig_Extensions_TokenParser_Trans());
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
             new Twig_SimpleFilter('trans', 'zenario_phrase')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'i18n';
    }
}
