<template>
    <Card class="px-4 py-4 space-y-4">
        <div
            class="flex mb-3 relative"
            v-if="state.dueIn"
        >
            <span
                class="whitespace-no-wrap px-2 py-1 rounded-full uppercase text-xs font-bold absolute pin-t pin-r"
                :class="{ 'bg-orange-light text-orange-dark': state.duePast === false, 'bg-red-light text-red-dark': state.duePast === true }"
            >
                {{ __("Due") }} {{ state.dueIn }}
            </span>
        </div>
        <div class="flex items-start">
            <div
                class="rounded-full mr-3 mt-1"
                style="height: 20px; width: 23px"
                :class="'bg-' + state.color"
            ></div>
            <div class="flex justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold">{{ state.title }}</h2>
                    <p class="text-sm text-gray-500 mb-3">
                        {{ state.description }}
                    </p>
                    <Dropdown v-if="state.transitions && state.transitions.length > 2">
                        <span class="sr-only">{{ __("Resource Row Dropdown") }}</span>
                        <DropdownTrigger
                            :dusk="`${resource.id.value}-control-selector`"
                            :show-arrow="false"
                            class="rounded hover:bg-gray-200 dark:hover:bg-gray-800 focus:outline-none focus:ring"
                        >
                            <OutlineButton component="span">Status wechseln</OutlineButton>
                        </DropdownTrigger>

                        <template #menu>
                            <DropdownMenu
                                width="auto"
                                class="px-1"
                            >
                                <ScrollWrap
                                    :height="250"
                                    class="divide-y divide-gray-100 dark:divide-gray-800 divide-solid"
                                >
                                    <div
                                        v-if="state.transitions && state.transitions.length"
                                        :dusk="`${resource.id.value}-inline-actions`"
                                        class="py-1"
                                    >
                                        <!-- User Actions -->
                                        <DropdownMenuItem
                                            as="button"
                                            v-for="transition in state.transitions"
                                            :key="transition.name"
                                            @click.stop.prevent="apply(transition)"
                                            :title="transition.title"
                                        >
                                            {{ transition.title }}
                                        </DropdownMenuItem>
                                    </div>
                                </ScrollWrap>
                            </DropdownMenu>
                        </template>
                    </Dropdown>
                    <div
                        v-if="state.transitions && state.transitions.length <= 2"
                        class="flex flex-col space-y-2"
                        style="align-items: baseline"
                    >
                        <DefaultButton
                            :disabled="loading"
                            v-for="transition in state.transitions"
                            :key="transition.name"
                            @click.stop.prevent="apply(transition)"
                        >
                            {{ transition.title }}
                        </DefaultButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="state && state.responsibleUsers.length !== 0">
            <h3 class="uppercase tracking-wide font-bold">{{ __("Responsibility") }}</h3>
            <h5 class="font-light">
                <span
                    v-for="user in state.responsibleUsers"
                    :key="user.id"
                >
                    <router-link
                        :to="{
                            name: 'detail',
                            params: {
                                resourceName: user.resourceName,
                                resourceId: user.id,
                            },
                        }"
                    >
                        {{ user.name }}
                    </router-link>
                    ,&nbsp;
                </span>
            </h5>
        </div>

        <div class="action-selector hidden">
            <ActionDropdown
                ref="actionSelector"
                :resource="resource"
                :actions="actions"
                :resource-name="resourceName"
                @actionExecuted="$emit('actionExecuted')"
                :selected-resources="[resource.id.value]"
            ></ActionDropdown>
        </div>
    </Card>
</template>

<script>
import { Inertia } from "@inertiajs/inertia";
export default {
    props: ["card", "resource", "resourceId", "resourceName"],
    data: () => ({
        state: 0,
        loading: false,
        actions: [],
    }),
    async mounted() {
        await this.reloadStatus();
        this.state.transition = [...this.state.transitions.filter((item) => item.userInteraction === true)];

        Nova.$on("action-executed", () => {
            Inertia.reload();
        });
        this.getActions();
    },
    methods: {
        getActions() {
            this.actions = [];
            return Nova.request()
                .get("/nova-api/" + this.resourceName + "/actions", {
                    params: {
                        resourceId: this.resourceId,
                        editing: true,
                        editMode: "create",
                        display: "",
                    },
                })
                .then((response) => {
                    this.actions = response.data.actions;

                    this.actions
                        .filter((i) => i.uriKey === "workflow-status-change")
                        .map((i) => {
                            i.withoutConfirmation = true;

                            return i;
                        });
                });
        },
        apply(transition) {
            this.loading = true;

            const query = new URLSearchParams(window.location.search);
            query.set("transition", transition.name);
            const newUrl = `${window.location.origin}${window.location.pathname}?${query.toString()}`;
            window.history.replaceState({ path: newUrl }, "", newUrl);

            document.querySelector(`[data-action-id="${transition.action}"]`).click();
        },
        async reloadStatus() {
            this.state = (await Nova.request().get(`/nova-vendor/nova-workflow/workflow?resourceName=${this.resourceName}&resourceId=${this.resourceId}`)).data;
        },
    },
};
</script>
<style scoped>
.action-selector > div {
    visibility: hidden;
    display: none;
}
.action-selector > div.modal {
    visibility: visible;
    display: block;
}
</style>
