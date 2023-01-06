{{extends file="page.tpl"}}
{block name='page_content'}

    {if $shops|count === 0}
        <h1 id="shops_title">Nous n'avons malheureusement pas de commerces en France</h1>
    {elseif $shops|count === 1}
        <h1 id="shops_title">Nous avons {$shops|count} commerce en France</h1>
    {elseif $shops|count > 1}
        <h1 id="shops_title">Nous avons {$shops|count} commerces en France</h1>
    {/if}
    <div id="map"></div>
    {foreach from=$shops item=$i}
        <script>
            window.onload = setTimeout( function(){
                let marker = new L.Marker([{$i['myShopLat']}, {$i['myShopLong']}]).addTo(map);
                marker.bindPopup('<h4>{$i['myShopName']}</h4> <br> - Adresse : {$i['myShopAddress']} <br> - Tel : {$i['myShopTel']} ');
            }, 1000);
        </script>
    {/foreach}
    {foreach from=$shops item=$i}
        <div id="shops" class="my-2 p-2">
            <h2>Boutique - {$i['myShopName']}</h2>
            <p class="m-0">{$i['myShopAddress']}</p>
            <p class="m-0">{$i['myShopTel']}</p>
        </div>
    {/foreach}
{/block}

{block name='javascript_bottom' append}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
     integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI="
     crossorigin=""/>
{/block}
