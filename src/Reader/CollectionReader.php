<?php
/**
 * PostRenderer.php
 *
 * Created By: jonathan
 * Date: 24/09/2017
 * Time: 22:17
 */

namespace Stati\Reader;

use Symfony\Component\Finder\Finder;
use Stati\Entity\Post;
use Stati\Entity\Collection;

/**
 * Renders collection defined in _config.yml
 * Class CollectionRenderer
 * @package Stati\Renderer
 */
class CollectionReader extends Reader
{
    public function read()
    {
        if (!isset($this->config['collections']) || !is_array($this->config['collections'])) {
            return null;
        }

        $collections = [];
        foreach ($this->config['collections'] as $collectionName => $collectionData) {
            $collection = new Collection($collectionName, $collectionData);
            $finder = new Finder();
            $finder
                ->in('./')
                ->path(sprintf('/^_%s/', $collectionName))
                ->files()
                ->contains('/---\s+(.*)\s+---\s+/s');
            $posts = [];
            foreach ($finder as $file) {
                $posts[] = new Post($file, $collectionData);

            }
            usort($posts, function($a, $b) {
                return $a->getDate()->getTimestamp() > $b->getDate()->getTimestamp() ? -1 : 1;
            });
            $collection->setDocs($posts);
            $collections[$collectionName] = $collection;
        }

        return $collections;
    }
}