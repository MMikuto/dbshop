<template>
<div>


  <Navbar />

  <router-view/>

  <footer />
  
  </div>
  
</template>



<script>
import Navbar from "./components/Navbar.vue";
import footer from "./components/footer.vue";
export default {
  name: "App",
  components: { Navbar,footer }
};
</script>
