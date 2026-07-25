function u(n,{category:r=null,lift:f=null,equipment:t=null}={}){return(n??[]).filter(l=>!(r&&l.category!==r||f&&l.lift!==f&&l.lift!=="general"||t&&l.equipment!==t))}export{u as f};
