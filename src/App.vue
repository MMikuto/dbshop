<template>
<div>


  <Navbar />

  <router-view/>

  <FooterComponent />
  
  </div>
  
</template>



<script>
import Navbar from "./components/Navbar.vue";
import FooterComponent from "./components/footer.vue";
export default {
  name: "App",
  components: { Navbar, FooterComponent }
};
</script>
