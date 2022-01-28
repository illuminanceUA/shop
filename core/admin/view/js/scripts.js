document.querySelector('.sitemap-button').onclick = (e) => {

    e.preventDefault();

    Ajax({type: 'POST'})
        .then((res) => {
            console.log('Успех - ' + res)
        })
        .catch((res) => {
            console.log('Ошибка - ' + res)
        });

}