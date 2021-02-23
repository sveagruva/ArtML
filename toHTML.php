class ArtML{
    function __construct(string $ArtMLstr) {
        $parser = xml_parser_create();
        if(xml_parse_into_struct($parser, $ArtMLstr, $this->_tags) != 1)
            throw new Error("incorrect xml structure");

        xml_parser_free($parser);
        self::ValidateArtMLHeader($this->_tags[0]);

        foreach ($this->_tags as $tag) {
            if ($tag["tag"] == "ARTICLE")
                continue;

            if (!in_array($tag["tag"], self::allowedTags))
                throw new Error("tag " . $tag["tag"] . " is not supported by ArtML standard");

            if (!in_array($tag["tag"], $this->uniqueTags))
                array_push($this->uniqueTags, $tag["tag"]);
        }
    }

    /**
     * @param bool $isAMP if you need to know what tags are used you can access $uniqueTags variable
     * @return string html code (without body, article tags or anything else, only parsed content)
     */
    public function GetHTML(bool $isAMP = false){
        $html = "";$arrayStorage = array();


        // there is open, closed and complete tags.
        // if it is open so somewhere it's going to close.
        // this defines if tag was open back then and it should look for close tag.
        $inSearch = false;
        // indicates what tag was open
        $tagType = "article";


        foreach ($this->_tags as $tag) {
            if($tag["tag"] == "ARTICLE")
                continue;

            if($inSearch){
                // putting pieces of tag that are being searched
                array_push($arrayStorage, $tag);
                // if it not closing tag of the right type it should continue searching
                if(!($tag["type"] == "close" && $tag["tag"] == $tagType))
                    continue;

                // closing tag was found. resetting vars and handle tag.
                $html .= self::_CompleteTagHandler($arrayStorage, $isAMP);

                $tagType = "article";
                $inSearch = false;
                $arrayStorage = array();
            }else{
                if($tag["type"] == "open"){
                    // if it open it should start searching for closing tag
                    $inSearch = true;
                    $tagType = $tag["tag"];

                    //$arrayStorage = array();
                    array_push($arrayStorage, $tag);
                }else{
                    $html .= self::_CompleteTagHandler([ $tag ], $isAMP);
                }
            }
        }

        return $html;
    }


    /**
     * @param array $tags
     * @param boolean $amp defines if html code should be amp compatible
     * @return string html
     * @meta it handles already completed tag
     */
    private static function _CompleteTagHandler(array $tags, bool $amp){
        switch ($tags[0]["tag"]) {
            case "P":
                return self::_Tag_P_Like_handler($tags);
            case "IMAGE":
                return self::_Tag_IMAGE_handler($tags, $amp);
            case "Q":
                return self::_Tag_Q_handler($tags);
            case "OL":
            case "UL":
                return self::_Tag_LIST_handler($tags);
            case "VIDEO":
                return self::_Tag_VIDEO_handler($tags, $amp);
            case "SLIDER":
                return self::_Tag_SLIDER_handler($tags, $amp);
            case "TWITTER":
                return self::_Tag_TWITTER_handler($tags, $amp);
            case "YB":
                return self::_Tag_YB_handler($tags, $amp);
            default:
                throw new Error("implementation for " . $tags[0]["tag"] . " tag not found");
        }
    }



    private static function _Tag_P_Like_handler(array $tags, string $wrapperTag = "p", bool $allowQ = true){
        $attributes = null;

        if(array_key_exists("attributes", $tags[0]))
            $attributes = $tags[0]["attributes"];

        if($tags[0]["type"] == "complete"){
            if(is_null($attributes))
                return self::_TagReturner($wrapperTag, $tags[0]["value"]);
            else
                return self::_TagReturner($wrapperTag, self::_Tag_TM_handler($attributes, $tags[0]["value"], $allowQ));
        }

        $content = "";
        foreach($tags as $tag){
            if(strtolower($tag["tag"]) == $wrapperTag){
                if (!array_key_exists('value', $tag)) continue;

                if(is_null($attributes))
                    $content .= $tag["value"];
                else
                    $content .= self::_Tag_TM_handler($attributes, $tag["value"], false);
            }elseif($tag["tag"] == "TM"){
                $content .= self::_Tag_TM_handler($tag["attributes"], $tag["value"], $allowQ);
            }else throw new Error($wrapperTag . "tag not supposed to have anything inside except text or tm tags");
        }

        return self::_TagReturner($wrapperTag, $content);
    }

    private static function _Tag_TM_handler(array $attributes, string $content, bool $allowQ = true){
        if(array_key_exists("I", $attributes))
            if($attributes["I"] == "n")
                $content = self::_TagReturner("em", $content);
            else
                $content = self::_TagReturner("i", $content);

        if(array_key_exists("B", $attributes)){
            if($attributes["B"] == "n")
                $content = self::_TagReturner("strong", $content);
            else
                $content = self::_TagReturner("b", $content);
        }

        if(array_key_exists("S", $attributes))
            $content = '<a href="' . self::LinkTransformer($attributes["S"]) . '">' . $content . '</a>';

        if(array_key_exists("Q", $attributes)){
            if(!$allowQ)
                throw new Error("tm tag cannot have q attribute in this case");

            if($attributes["Q"] == "" && array_key_exists("S", $attributes))
                $content = '<q cite="' . self::LinkTransformer($attributes["S"]) . '">' . $content . "</q>";
            elseif($attributes["Q"] != "null" && $attributes["Q"] != "")
                $content = '<q cite="' . self::LinkTransformer($attributes["Q"]) . '">' . $content . "</q>";
            else
                $content = self::_TagReturner("q", $content);
        }

        return $content;
    }

    private static function _Tag_IMAGE_handler(array $tag, $amp){
        if(count($tag) != 1 || $tag[0]["type"] != "complete")
            throw new Error("image tag is stand alone tag");

        $tag = $tag[0];
        $w = $tag["attributes"]["W"];
        $h = $tag["attributes"]["H"];
        $link = self::LinkTransformer($tag["value"]);

        if(!is_numeric($w) || !is_numeric($h))
            throw new Error("w or h or both attributes of image tag is not an integer value");


        if($amp){
            $htmlTag = '<amp-img layout="responsive" width="' . $h . 'px" height="' . $w . 'px" src="' . $link . '"';

            if(array_key_exists("A", $tag["attributes"]))
                $htmlTag .= 'alt="' . $tag["attributes"]["A"] . '" ';

            $htmlTag .= "</amp-img>";
        }else{
            $htmlTag = '<img src="' . $link . '" width="' . $w . 'px" height="' . $h . 'px"';

            if(array_key_exists("A", $tag["attributes"]))
                $htmlTag .= ' alt="' . $tag["attributes"]["A"] . '"';

            $htmlTag .= ">";
        }

        if(array_key_exists("S", $tag["attributes"]))
            return '<a href="' . self::LinkTransformer($tag["attributes"]["S"]) . '">' . $htmlTag . "</a>";

        return $htmlTag;
    }

    private static function _Tag_VIDEO_handler(array $tag, $amp){
        if(count($tag) != 1 || $tag[0]["type"] != "complete")
            throw new Error("video is a stand alone tag");

        $tag = $tag[0];

        if(!array_key_exists("W", $tag["attributes"]) || !array_key_exists("H", $tag["attributes"]))
            throw new Error("video tag must have w and h attributes");

        $shared = 'width="' . $tag["attributes"]["W"] . 'px" height="' . $tag["attributes"]["H"] . 'px" controls';

        if($amp)
            return '<amp-video ' . $shared . ' layout="responsive"><source src="' . self::LinkTransformer($tag["value"]) . '"></amp-video>';
        else
            return '<video ' . $shared . '><source src="' . self::LinkTransformer($tag["value"]) . '"></video>';
    }

    private static function _Tag_Q_handler(array $tags){
        if(count($tags) == 1 && $tags[0]["type"] == "complete")
            return "<blockquote>" . $tags[0]["value"] . "</blockquote>";

        $arrayStorage = array();
        $html = "<blockquote>";

        foreach ($tags as $tag) {
            if($tag["tag"] != "Q"){
                array_push($arrayStorage, $tag);
                continue;
            }

            if(count($arrayStorage) != 0){
                $html .= self::_Tag_P_Like_handler($arrayStorage, "p", false);
                $arrayStorage = array();
            }

            if(array_key_exists("value", $tag) && !is_null($tag["value"]))
                $html .= "<p>" . $tag["value"] . "</p>";
        }

        return $html . "</blockquote>";
    }

    private static function _Tag_SLIDER_handler(array $tags, $amp){
        $html = '<div class="slider">';
        foreach($tags as $tag){
            if($tag["tag"] == "SLIDER")
                continue;

            if($tag["tag"] != "IMAGE")
                throw new Error("slider can only contain image tags");

            $html .= self::_Tag_IMAGE_handler([$tag], $amp);
        }

        return $html . '</div>';
    }

    private static function _Tag_TWITTER_handler(array $tag, $amp){
        // this function will not make any requests to twitter api for getting content or author.

        if(count($tag) != 1 || $tag[0]["type"] != "complete")
            throw new Error("twitter is a stand alone tag");

        $tweetId = $tag[0]["value"];

        if(strlen($tweetId) != 18 || !is_numeric($tweetId))
            throw new Error("tweet id must be 18 digits");

        if($amp)
            return '<amp-twitter width="400" height="400" layout="responsive" data-tweetid="' . $tweetId. '"></amp-twitter>';

        if(array_key_exists("AUTHOR", $tag[0]["attributes"])) {
            $authorStr = $tag[0]["attributes"]["AUTHOR"];
            $pos = strpos($authorStr, "@");
            if($pos === false)
                throw new Error("not correct author attribute in twitter tag");

            $author = substr($authorStr, 0, $pos);
            $authorId = substr($authorStr, $pos + 1);
        }else{
            $author = "author";
            $authorId = "author";
        }

        $tweetLink = 'https://twitter.com/' . $authorId . '/status/' . $tweetId;

        $content = "";
        if(array_key_exists("CONTENT", $tag[0]["attributes"]))
            $content = $tag[0]["attributes"]["CONTENT"];

        //return '<blockquote class="twitter-tweet" cite="' . $tweetLink . '"><p>' . $content . ' <a href="' . $tweetLink .'"> —' . $author . '</a></p></blockquote>';
        return '<blockquote class="twitter-tweet tw-align-center"><p>' . $content . ' <br><br><a href="' . $tweetLink .'"> —' . $author . '</a></p></blockquote>';
    }

    private static function _Tag_LIST_handler(array $tags){
        $listTag = $tags[0]["tag"];
        $html = "";
        $arrayStorage = array();
        $isLi = false;

        foreach ($tags as $tag) {
            if($tag["tag"] == $listTag) continue;

            if($tag["tag"] == "LI"){
                if ($tag["type"] == "complete"){
                    $html .= self::_Tag_P_Like_handler([$tag], "li");
                    continue;
                }

                array_push($arrayStorage, $tag);
                if($tag["type"] == "close"){
                    $html .= self::_Tag_P_Like_handler($arrayStorage, "li");
                    $arrayStorage = array();
                }
            }else
                array_push($arrayStorage, $tag);
        }

        return self::_TagReturner($listTag, $html);
    }

    private static function _Tag_YB_handler(array $tag, bool $amp){
        if(count($tag) != 1)
            throw new Error("YT is a standalone tag");

        $youtubeId = $tag[0]["value"];
        if(!is_string($youtubeId) || strlen($youtubeId) != 11)
            throw new Error("youtube id must be string with 11 chars");

        if($amp)
            return '<div class="youtube_wrapper"><div><amp-youtube data-videoid="' . $youtubeId . '" layout="responsive" height="400px" width="730px"></amp-youtube></div></div>';
        else
            return '<div class="youtube_wrapper"><div><iframe src="https://www.youtube.com/embed/' . $youtubeId . '"></iframe></div></div>';
    }

    private static function _TagReturner($tag, $content){
        $tag = strtolower($tag);
        return '<' . $tag . '>' . $content . '</' . $tag . '>';
    }

    public static function LinkTransformer($link){
        if($link[0] == "l")
            return $link;
            // custom protocol

        if($link[1] != ":")
            throw new Error("not a valid protocol");

        if($link[0] == "s")
            return "https://" . substr($link, 2);

        if($link[0] == "p")
            return "http://" . substr($link, 2);

        throw new Error($link[0] . " is not a valid protocol");
    }



    private array $_tags = array();
    private static array $_requiredAttributesInArticleHeader = [
        "TITLE",
        "LANGUAGE"
    ];

    public array $uniqueTags = array();
    public const allowedTags = [
        "P",
        "TM",
        "Q",
        "UL",
        "OL",
        "LI",
        "IMAGE",
        "VIDEO",
        "SLIDER",
        "TWITTER",
        "YB"
    ];

    public static function ValidateArtMLHeader(array $articleHeader){
        if($articleHeader["tag"] != "ARTICLE")
            throw new Error("article must be a root element");

        foreach(self::$_requiredAttributesInArticleHeader as $requirementAttribute)
            if(!array_key_exists($requirementAttribute, $articleHeader["attributes"]))
                throw new Error("article must have attribute: " . $requirementAttribute);
    }
}
