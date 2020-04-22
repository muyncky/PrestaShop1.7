<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5ea00cc67502b\Symfony\Component\Cache\Simple;

use _PhpScoper5ea00cc67502b\Doctrine\Common\Cache\CacheProvider;
use _PhpScoper5ea00cc67502b\Symfony\Component\Cache\Traits\DoctrineTrait;
class DoctrineCache extends \_PhpScoper5ea00cc67502b\Symfony\Component\Cache\Simple\AbstractCache
{
    use DoctrineTrait;
    /**
     * @param string $namespace
     * @param int    $defaultLifetime
     */
    public function __construct(\_PhpScoper5ea00cc67502b\Doctrine\Common\Cache\CacheProvider $provider, $namespace = '', $defaultLifetime = 0)
    {
        parent::__construct('', $defaultLifetime);
        $this->provider = $provider;
        $provider->setNamespace($namespace);
    }
}
