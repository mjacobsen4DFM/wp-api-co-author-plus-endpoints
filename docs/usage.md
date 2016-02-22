## Usage: How to query for authors

This plugin can query for author data and can add authors via the author term. 
It can also add a co-author to a post by using the 'author' term associated with the co-author.

### Background on Co-Authors Plus
---
Co-Authors Plus stores authors as a association between terms with 'author' taxonomy and posts of post_type 'guest-author'.
That is the straight forward use for adding co-authors who are not actual usesr.
The cli tool adds users to this equation. If a user exists then the user.id is used rather than tehcustom post type. This complicates querying for user information, as you need to get either a post or a user on a conditional basis; however, the 'author' term is still the common link point. To this end, the endpoint for getting author details will return wp_user and/or wp_post, as appropriate.

### Endpoints
---
#### Top level endpoints
`http://example.com/wp-json/co-authors/v1/author-terms` - **Returns all co-author terms**

`http://example.com/wp-json/co-authors/v1/author-posts` - **Returns all co-author posts**

`http://example.com/wp-json/co-authors/v1/author-users` - **Returns all co-author users**

#### Post related endpoints
`http://example.com/wp-json/co-authors/v1/posts/60946/author-terms` - **Returns all co-author terms associated with the post**

`http://example.com/wp-json/co-authors/v1/posts/60946/author-posts` - **Returns all co-author posts associated with the post**

`http://example.com/wp-json/co-authors/v1/posts/60946/author-users` - **Returns all co-author users associated with the post**

___

### Examples
---
#### author-terms
```
{
   "id":913,
   "name":"jones",
   "slug":"cap-jones",
   "term_group ":0,
   "term_taxonomy_id":913,
   "taxonomy":"author",
   "description":"jones Bill S. Jones bjones 87 bjones@example.com",
   "parent":0,
   "count":1443,
   "_links":{
      "about":[
         {
            "embeddable":true,
            "href":"http:\/\/example.com\/wp-json\/co-authors\/v1\/author-terms\/913"
         }
      ]
   }
}
```

#### author-posts
```
{
   "id":11052,
   "post_name":"cap-jane-smith",
   "post_type":"guest-author",
   "post_title":"jsmith",
   "post_date":"2015-10-21 19:43:04",
   "_links":{
      "about":[
         {
            "embeddable":true,
            "href":"http:\/\/example.com\/wp-json\/co-authors\/v1\/author-posts\/11052"
         }
      ]
   }
}
```

#### author-users
```
{
   "id":87,
   "first_name":"Bill S.",
   "last_name":"Jones",
   "display_name":"Bill S. Jones",
   "_links":{
      "about":[
         {
            "embeddable":true,
            "href":"http:\/\/example.com\/wp-json\/co-authors\/v1\/author-users\/87"
         }
      ]
   }
}
```

___


---
### Adding an author to a post
---
####Using author-terms
You can use 2 methods for attaching an author to a post.

1. Passing the author term ID via the REST URL:
`http://example.com/wp-json/co-authors/v1/posts/60946/author-terms/875`

2. Passing a JSON as the body of a RESTful Post:
`http://example.com/wp-json/co-authors/v1/posts/60946/author-terms/` with JSON in the body: `{ "coauthor_id":875 }`

#### Both will return:
```
{
   "id":875,
   "name":"west",
   "slug":"cap-west",
   "term_group ":0,
   "term_taxonomy_id":875,
   "taxonomy":"author",
   "description":"west North West nwest 45 nwest@example.com",
   "parent":0,
   "count":327,
   "_links":{
      "about":[
         {
            "embeddable":true,
            "href":"http:\/\/example.com\/wp-json\/co-authors\/v1\/author-terms\/875"
         }
      ]
   }
}
```

( FYI, the above author-term points to a real user with ID=45, as can be seen in the "description" )
___


**TO-DO**: make the JSON, capable of adding multiple co-authors via an array: ```{ "coauthor_id":[913, 358, 875] }```


___

