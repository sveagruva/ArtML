# ArtML


**Artml is a xml based standart for containing articles**


## Article element
- article is a root element
- attrubites in an article element discribe article. full list of them:
    - title **required**
    - publish, date of publish in ISO 8601
    - edit, date of last edit in ISO 8601
    - except. should not inclide double quote symbol - < " >
    - front, url to an image that are stands as header or as cover. check [this](#url-rules) for url rules
    - language, language in ISO 639-2 format (always 3 characters). eng for english  **required** 
- the only allowed xml encoding utf-8 others will be considered as errors
- only allowed xml version is 1.0 others will be considered as errors

        <?xml version="1.0" encoding="UTF-8"?>
        <article>
            <p>
                hello world
            </p>
        </article>


## Elements that can be used in artml

### p
tag can contain all set of characters (except '<' and '>') and tag tm. p tag can't contain another p tag. P tag itself can have same attributes as tm tag do except 'q' attribute.

### tm
tm is a text-modificator. It can't have anything inside except of text. modificators are attributes. content of them can be anything, it is not an error.

- s - makes a link, check [this](#url-rules) for url rules
- b - makes bold text, if it's important (strong in html) should have "n" inside
- i - makes italic text, if it's emphasised (em in html) should have "n" inside
- q - makes quote, contains source of quote. If have to be same as s attribute it should have empty string as value q="". If doesn't have any source it should have null as value q="null" or empty string, when using empty string it will use s attribute if it exists (cite attribute for html)

      <p> hello friend, <tm b="anything - jfkasdjasfasdfasdfasdf, this will be ignored"> nice to meet you again </tm></p>
      <tm b="n"> important</tm>
      
      //<tm b>text</tm> is INVALID


### q
tag contain same set of characters and tags as p tag does. q tag stands for quote and it can have p tag inside, but can't have tm tags with "q" attribute.

    <p>They said </p>
    <q>it cannot be done</q>


### ul, ol
tags very similar to html version. It can only contain li tags inside.

### li
tag is an item for lists(ul, ol tags). can contain text and tm tags. li tag can have same attributes as tm tag do.

    <ul>
        <li b="">hello</li>
    </ul>

### image
tag contain url to an image, check [this](#url-rules) for url rules.
images can be in these formats: png, jpg(jpeg), webp, gif

- w, for defining width of an image in pixels **required**
- h, for defining hegith of an image in pixels **required**
- a, for defining text description of an image
- s, for defining url that user should be forwarded when image being clicked. check [this](#url-rules) for url rules


        <image w="1300" h="1300">s:api.sveagruva.site/static/thor/1.png</image>
    

### video
tag contain url to a video, check [this](#url-rules) for url rules 

- w, for defining width of an video in pixels **required**
- h, for defining hegith of an video in pixels **required**

        <video w="1300" h="1300">s:example.com/video.mp4</video>


### slider
tag can contain only image tags

    <slider> 
        <image w="1300" h="1300">s:api.sveagruva.site/static/thor/1.png</image>
        <image w="1300" h="1300">s:api.sveagruva.site/static/thor/1.png</image>
    </slider>


### twitter
tag contains 18 digits otherwise not valid **required**

- author, include author of a tweet in format with profile(visible) name first and nickname second, divided by 'at' sign
- content, containing content of a tweet. It works like [p tag](#p)  **optional**

        <twitter author="Donald J. Trump@realDonaldTrump" content="USA!!!">123456789012345678</twitter>

### yb(youtube)
tag contains 11 characters otherwise not valid

    <yb>12345678901</yb>





## Url rules
url starts with s for https and with p for http  and followed by ':' symbol. the rest part is the rest part of a link. for example:

        s:api.sveagruva.site/static/thor/1.png
        
mean

        https://api.sveagruva.site/static/thor/1.png
        
for describing resources that have to be transferred another way can be used l protocol or any other protocol that you can make yourself that are started with l
        
        l:/base64/image/png/1
        l_my_protocol:/base64/image/png/1
