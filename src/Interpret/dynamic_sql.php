<?php

/**
<select id="findActiveBlogWithTitleLike" resultType="Blog">
    SELECT * FROM BLOG WHERE state = ‘ACTIVE’
    <if test="title != null">
        AND title like #{title}
    </if>
    <if test="author != null and author.name != null">
        AND author_name like #{author.name}
    </if>
</select>
 */

$ast = [
    'SELECT * FROM BLOG WHERE state = "ACTIVE"',
    ['$if',
        ['<>', '$title', 'null'],
        'AND title like #{title}'],
    ['$if',
        ['and',
                ['<>', '$author', 'null'],
                ['<>', '$author["name"]', 'null']],
        'AND title like #{title}']
];


function parse($node, $sql = [])
{
    if (is_array($node)) {
        $firstNode = $node[0];
        switch ($firstNode) {
            case is_string($firstNode):
                switch ($firstNode[0]) {
                    case '$if':

                        break;
                }
                break;
            default:
                break;
        }
    } else {
        $sql[] = $node;
        return $sql;
    }
}

/**
<select id="findActiveBlogLike"
resultType="Blog">
SELECT * FROM BLOG WHERE state = ‘ACTIVE’
<choose>
<when test="title != null">
AND title like #{title}
</when>
<when test="author != null and author.name != null">
AND author_name like #{author.name}
</when>
<otherwise>
AND featured = 1
</otherwise>
</choose>
</select>
 */

$ast = [
    'SELECT * FROM BLOG WHERE state = "ACTIVE"',
    ['$choose',
        ['$when', '$title != null', 'AND title like #{title}'],
        ['$when', '$author != null and $author["name"] != null', 'AND author_name like #{author.name}'],
        ['$otherwise', 'AND featured = 1']],
];

/**
<select id="selectPostIn" resultType="domain.blog.Post">
SELECT *
FROM POST P
WHERE ID in
<foreach item="item" index="index" collection="list"
open="(" separator="," close=")">
#{item}
</foreach>
</select>
 */

$ast = [
    'SELECT * FROM POST P WHERE ID in ',
    ['$foreach', [
        'index' => '$index',
        'item' => '$item',
        'open' => '(',
        'close' => ')',
        'seq' => ',',
    ], '#{item}']
];