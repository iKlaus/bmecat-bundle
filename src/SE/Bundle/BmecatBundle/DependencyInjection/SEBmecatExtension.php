<?php
/**
 * This file is part of the BMEcat php library
 *
 * (c) Sven Eisenschmidt <sven.eisenschmidt@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SE\Bundle\BmecatBundle\DependencyInjection;

use SE\Component\BMEcat\DocumentBuilder;
use SE\Component\BMEcat\NodeLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 * @package SE\Bundle\BmecatBundle
 * @author  Sven Eisenschmidt <sven.eisenschmidt@gmail.com>
 */
class SEBmecatExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $documents = $config['documents'];
        foreach ($documents as $id => $document) {
            $name = 'se.bmecat.document_builder.' . $id;
            $nodeLoaderDefinition = new Definition(NodeLoader::class);

            foreach ($document['loader'] as $nodeName => $class) {
                $nodeLoaderDefinition->addMethodCall('set', array($nodeName, trim($class, '\\')));
            }

            $serializerReference = new Reference('jms_serializer', ContainerInterface::NULL_ON_INVALID_REFERENCE);

            $definition = new Definition(DocumentBuilder::class, [$serializerReference, $nodeLoaderDefinition]);
            $definition->addMethodCall('load', [$document]);

            $container->setDefinition($name, $definition);

            $manager = $container->findDefinition('se.bmecat.document_builder_manager');
            $manager->addMethodCall('addDocumentBuilder', [$id, new Reference($name)]);
        }
    }
}
