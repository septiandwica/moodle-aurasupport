<?php
// Dummy test file
?>
<script>
    var originalDefine = window.define;
    if (originalDefine && originalDefine.amd) {
        window.moodleAmd = originalDefine.amd;
        originalDefine.amd = false;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>
<script>
    if (window.originalDefine && window.moodleAmd) {
        window.originalDefine.amd = window.moodleAmd;
    }
</script>
