</main>
<footer>
    <div class="container" style="text-align: center; margin-top: 50px; color: #777;">
        <p>&copy; <?php echo date("Y"); ?> Website KasKeuangan Khresmupu</p>
    </div>
</footer>

<script>
function saveData(el, id, kolom) {
    let nilai = el.innerText.replace(/\./g, '');

    // Path diubah mengarah ke dalam folder pages/
    fetch('pages/update_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + id + '&kolom=' + kolom + '&nilai=' + encodeURIComponent(nilai)
        })
        .then(response => response.text())
        .then(data => {
            console.log('Update berhasil');
            // Tambahkan reload agar saldo terhitung ulang secara akurat
            location.reload();
        });


}
</script>
</body>

</html>