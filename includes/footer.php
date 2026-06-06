<?php
// includes/footer.php
// Main Layout Footer Template
?>

<?php if (is_logged_in() && isset($active_page) && $active_page !== 'login' && $active_page !== 'landing'): ?>
    </main>
</div>
<?php endif; ?>

<!-- Core JavaScript -->
<script src="js/main.js"></script>

</body>
</html>
