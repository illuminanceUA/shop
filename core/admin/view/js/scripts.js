document.querySelector('.sitemap-button').onclick = (e) => {

    e.preventDefault();

    createSiteMap();

}

let linksCounter = 0;

function createSiteMap(){

    linksCounter++;

    Ajax({data: {ajax:'sitemap', linksCounter: linksCounter}})
        .then((res) => {
            console.log('Успех - ' + res)
        })
        .catch((res) => {
            console.log('Ошибка - ' + res)
            createSiteMap();
        });

}