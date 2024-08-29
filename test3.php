<script>
    jQuery(document).on('elementor/popup/show', () => {

        function hasClass(el, className)
        {
            if (el.classList)
                return el.classList.contains(className);
            return !!el.className.match(new RegExp('(\\s|^)' + className + '(\\s|$)'));
        }

        function addClass(el, className)
        {
            if (el.classList)
                el.classList.add(className)
            else if (!hasClass(el, className))
                el.className += " " + className;
        }

        function removeClass(el, className)
        {
            if (el.classList)
                el.classList.remove(className)
            else if (hasClass(el, className))
            {
                var reg = new RegExp('(\\s|^)' + className + '(\\s|$)');
                el.className = el.className.replace(reg, ' ');
            }
        }


        const active = "2px solid #26997e";
        const bgActive = "#f1f8f7";

        const nonActive = "2px solid #c7c6c9";
        const bgNonActive = "#fff";

        const disable = "2px solid black";
        const bgDisable = "#e5e5e7";
        var lmStat = false;
        var noStat = false;
        const l = document.getElementById('rent-model-price-3')
        const m = document.getElementById('rent-model-price-3-strike')
        const n = document.getElementById('rent-model-price-4')
        const o = document.getElementById('rent-model-price-4-strike')


        const back =document.getElementById('payment-btn-back')
        const divider =document.getElementById('which-internet-divide')
        const title =document.getElementById('title-rental-option')
        const p = document.getElementById('container-payment')
        const q = document.getElementById('rent-model-3')
        const r = document.getElementById('rent-model-4')
        const s = document.getElementById('container-proceed')
        const t = document.getElementById('payment-btn-proceed')

        back.style.display='none'
        s.style.display='none'

        q.onclick=payment
        r.onclick=payment


        addClass(back,'cursor-nomad')
        addClass(q,'cursor-nomad')
        addClass(r,'cursor-nomad')
        addClass(divider,'cursor-nomad')
        addClass(t,'cursor-nomad')

        back.onclick=backButton

        divider.onclick=close
        t.onclick=close


        function payment(){
            p.style.display='none'
            title.style.display='none'
            back.style.display='block'
            s.style.display='block'
        }
        function backButton(){
            p.style.display='flex'
            title.style.display='block'
            back.style.display='none'
            s.style.display='none'
        }

        function close(){
            document.querySelector('.dialog-close-button').click();
        }

    });
</script>
