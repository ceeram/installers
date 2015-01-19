<?php
namespace Composer\Installers;

use Composer\DependencyResolver\Pool;
use Composer\Package\PackageInterface;
use Composer\Package\LinkConstraint\MultiConstraint;
use Composer\Package\LinkConstraint\VersionConstraint;

class CakePHPInstaller extends BaseInstaller
{
    protected $locations = array(
        'plugin' => 'Plugin/{$name}/',
    );

    /**
     * Format package name to CamelCase
     */
    public function inflectPackageVars($vars)
    {
        $nameParts = explode('/', $vars['name']);
        foreach ($nameParts as &$value) {
            $value = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $value));
            $value = str_replace(array('-', '_'), ' ', $value);
            $value = str_replace(' ', '', ucwords($value));
        }
        $vars['name'] = implode('/', $nameParts);

        return $vars;
    }

    /**
     * Throw exception for CakePHP 3.x plugins
     *
     * @param PackageInterface $package
     * @param string $frameworkType
     * @return string
     * @throws \Exception
     */
    public function getInstallPath(PackageInterface $package, $frameworkType = '')
    {
        if ($this->matchesCakeVersion('>=', '3.0.0')) {
	        throw new \InvalidArgumentException('Package type "cakephp-plugin" for CakePHP 3.x must be installed with cakephp/plugin-installer.');
        }
        return parent::getInstallPath($package, $frameworkType);
    }

    /**
     * Check if CakePHP version matches against a version
     *
     * @param string $matcher
     * @param string $version
     * @return bool
     */
    protected function matchesCakeVersion($matcher, $version)
    {
        $repositoryManager = $this->composer->getRepositoryManager();
        if ($repositoryManager) {
            $repos = $repositoryManager->getLocalRepository();
            if (!$repos) {
                return false;
            }
            $cake3 = new MultiConstraint(array(
                new VersionConstraint($matcher, $version),
                new VersionConstraint('!=', '9999999-dev'),
            ));
            $pool = new Pool('dev');
            $pool->addRepository($repos);
            $packages = $pool->whatProvides('cakephp/cakephp');
            foreach ($packages as $package) {
                $installed = new VersionConstraint('=', $package->getVersion());
                if ($cake3->matches($installed)) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }

}
