var DocumentOutlineView = function documentOutlineView(outline)
{
    var outlineView = document.getElementById('outlineView');

    function bindItemLink(domObj, item)
    {
        domObj.href = XOJView.getDestinationHash(item.dest);
        domObj.onclick = function documentOutlineViewOnclick(e)
        {
            XOJView.navigateTo(item.dest);
            return false;
        };
    }

    var queue = [{parent: outlineView, items: outline}];
    while (queue.length > 0)
    {
        var levelData = queue.shift();
        var i, n = levelData.items.length;
        for (i = 0; i < n; i++)
        {
            var item = levelData.items[i];
            var div = document.createElement('div');
            div.className = 'outlineItem';
            var a = document.createElement('a');
            bindItemLink(a, item);
            a.textContent = item.title;
            div.appendChild(a);
            delete a;
            if (item.items.length > 0)
            {
                var itemsDiv = document.createElement('div');
                itemsDiv.className = 'outlineItems';
                div.appendChild(itemsDiv);
                queue.push({parent: itemsDiv, items: item.items});
            }
            
            delete item;
            
            
            levelData.parent.appendChild(div);
            delete div;
        }
        delete levelData;
        delete i;
        delete n;
    }
    delete queue;
};