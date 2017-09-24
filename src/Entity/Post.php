<?php
/**
 * Post.php
 *
 * Created By: jonathan
 * Date: 24/09/2017
 * Time: 23:05
 */

namespace Stati\Entity;

use Symfony\Component\Finder\SplFileInfo;
use Stati\Parser\FrontMatterParser;
use Stati\Parser\ContentParser;
use Stati\Parser\MarkdownParser;
use Liquid\Template;
use Liquid\Liquid;
use Stati\LiquidBlock\Highlight;
use Stati\LiquidTag\PostUrl;

class Post
{

    /**
     * HTML content of this post without layout
     * @var string
     */
    protected $content;

    /**
     * The post title
     * @var string
     */
    protected $title;

    /**
     * Original file for this post
     * @var SplFileInfo
     */
    protected $file;

    /**
     * URL for this post
     * @var string
     */
    protected $url;

    /**
     * Slug for this post
     * @var string
     */
    protected $slug;

    /**
     * Path for this post
     * @var string
     */
    protected $path;

    /**
     * Date for this post
     * @var \DateTime
     */
    protected $date;

    /**
     * front matter for this post
     * @var array
     */
    protected $frontMatter;

    /**
     * Permalink configuration
     * @var string
     */
    protected $permalink;

    /**
     * Site configuration configuration
     * @var array
     */
    protected $siteConfig;

    public function __construct(SplFileInfo $file, $siteConfig = [])
    {
        $this->file = $file;
        $this->siteConfig = $siteConfig;
    }

    private function getDate()
    {
        if ($this->date !== null) {
            return $this->date;
        }
        try {
            $this->date = new \DateTime(substr($this->file->getBasename(), 0, 10));
        } catch (\Exception $err) {
            echo $err->getMessage();
        }
        return $this->date;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $parser = new ContentParser();
        $markdownParser = new MarkdownParser();
        $contentPart = $parser::parse($this->file->getContents());
        $template = new Template('./_includes/');
        $template->registerTag('highlight', Highlight::class);
        $template->registerTag('post_url', PostUrl::class);
        $template->parse($contentPart);
        $config = [
            'page' => $this,
            'post' => $this,
            'site' => $this->siteConfig
        ];
        $liquidParsed = $template->render($config);
        $this->content = $markdownParser->text($liquidParsed);

        return $this->content;
    }


    /**
     * @param string $content
     * @return Post
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getFrontMatter()['title'];
    }

    /**
     * @param string $title
     * @return Post
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param SplFileInfo $file
     * @return Post
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->url !== null) {
            return $this->url;
        }

        $this->url = str_replace('//','/', $this->getPath());

    }

    /**
     * @param string $url
     * @return Post
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if ($this->path !== null) {
            return $this->path;
        }

        $link = $this->getPermalink();

        if ($this->getDate() && preg_match_all('/(:year|:month|:day|:hour)/', $link, $matches, PREG_PATTERN_ORDER)) {

            foreach ($matches[1] as $token) {
                $format = '';
                switch ($token) {
                    case ':year':
                        $format = 'Y';
                        break;
                    case ':month':
                        $format = 'm';
                        break;
                    case ':day':
                        $format = 'd';
                        break;
                    default:
                        continue;
                }

                $link = str_replace($token, $this->getDate()->format($format), $link);
            }
        }

        if (preg_match_all('/(:title|:categories|:slug)/', $link, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[1] as $token) {
                $replace = '';
                switch ($token) {
                    case ':title':
                        $replace = $this->getSlug();
                        break;
                    case ':categories':
                        $replace = implode('/', $this->getFrontMatter()['categories']);
                        break;
                    case ':slug':
                        $replace = $this->getSlug();
                        break;
                    default:
                        continue;
                }

                $link = str_replace($token, $replace, $link);
            }
        }

        $this->setPath($link);

        return $this->path;

    }

    /**
     * @param string $path
     * @return Post
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getFrontMatter()
    {
        if ($this->frontMatter) {
            return $this->frontMatter;
        }

        $parser = new FrontMatterParser();
        $this->setFrontMatter($parser::parse($this->file->getContents()));
        return $this->frontMatter;

    }

    /**
     * @param array $frontMatter
     * @return Post
     */
    public function setFrontMatter($frontMatter)
    {
        $this->frontMatter = $frontMatter;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        if ($this->slug) {
            return $this->slug;
        }
        $filename = pathinfo($this->file->getBasename(), PATHINFO_FILENAME);
        try {
            $date = new \DateTime(substr($filename, 0, 10));
            $this->slug = substr($filename, 11);
        } catch (\Exception $err) {
//            echo $err->getMessage();
            $this->slug = $filename;
        }
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getPermalink()
    {
        return $this->siteConfig['permalink'];
    }

    /**
     * @param string $permalink
     */
    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;
    }

    public function get($item)
    {
        return $this->__get($item);
    }

    public function __get($item)
    {
        var_dump('TRYING TO GET '.$item);
        if ($item === 'date') {
            var_dump($this->getDate()->format(DATE_RFC3339));
            return $this->getDate()->format(DATE_RFC3339);
        }
        if (method_exists(Post::class,'get'.ucfirst($item))) {
            var_dump('Calling getter for '.$item);
            var_dump($this->{'get'.ucfirst($item)}());
            return $this->{'get'.ucfirst($item)}();
        }
        if (isset($this->getFrontMatter()[$item])) {
            var_dump('Getting '.$item.' from frontmatter');
            var_dump($this->getFrontMatter()[$item]);
            return $this->getFrontMatter()[$item];
        }
        var_dump('FAILED TO GET '.$item);
        return null;
    }
}