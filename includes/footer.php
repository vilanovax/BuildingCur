    </main>

    <script>
        // تأیید حذف
        function confirmDelete(message) {
            return confirm(message || 'آیا مطمئن هستید؟');
        }

        // فرمت کردن اعداد به تومان
        function formatMoney(input) {
            let value = input.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                input.value = parseInt(value).toLocaleString('fa-IR');
            }
        }
    </script>
</body>
</html>
